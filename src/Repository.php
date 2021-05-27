<?php

namespace SimonHamp\Gitamic;

use Error;
use Exception;
use ErrorException;
use Statamic\Entries\Entry;
use Illuminate\Support\Str;
use Statamic\Facades\Stache;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Gitonomy\Git\Diff\File as GitFile;
use Gitonomy\Git\Exception\ProcessException;
use Gitonomy\Git\Repository as GitRepository;

class Repository implements Contracts\SiteRepository
{
    protected GitRepository $repo;

    const STATUS_BEHIND = 'Your branch is behind';
    const STATUS_AHEAD = 'Your branch is ahead';
    const STATUS_DIVERGED = 'have diverged';

    public function __construct(GitRepository $repo)
    {
        $this->repo = $repo;
    }

    public function repo(): GitRepository
    {
        return $this->repo;
    }

    public function getFilesOfType($type): Collection
    {
        $method = Str::camel("get_{$type}_files");

        return $this->$method();
    }

    public function getUnstagedFiles(): Collection
    {
        return $this->getUntrackedFiles()->merge($this->getPendingFiles())->keyBy('id');
    }

    public function getUntrackedFiles(): Collection
    {
        return collect($this->repo->getWorkingCopy()->getUntrackedFiles())
            ->transform(function ($relative_path, $id) {
                return $this->getFileDetails($relative_path, $id);
            });
    }

    public function getStagedFiles(): Collection
    {
        return collect($this->repo->getWorkingCopy()->getDiffStaged()->getFiles())
            ->transform(function (GitFile $file, $id) {
                return $this->getFileDetails($file, $id);
            });
    }

    public function getPendingFiles(): Collection
    {
        return collect($this->repo->getWorkingCopy()->getDiffPending()->getFiles())
            ->transform(function (GitFile $file, $id) {
                return $this->getFileDetails($file, $id);
            });
    }

    public function currentBranch(): array
    {
        return $this->branches()->where('current')->first();
    }

    public function branches(): Collection
    {
        return Cache::remember('gitamic.branches', config('gitamic.cache.ttl'), function() {
            $args = ['-vv'];

            $branches = trim($this->repo->run('branch', $args));

            return collect(preg_split("/((\r?\n)|(\r\n?))/", $branches))
                ->transform(function ($branch) {
                    Log::debug($branch);

                    // Parse the branch data for the different parts
                    preg_match('/^[\s|\*]?\s?(?<name>.*?)\s+(?<commit>[0-9A-f]{6,})\s\[?(?(?<=\[)(?<tracking>.*?)\:?|.*)\]?\s/', $branch, $matches);

                    return [
                        'current' => Str::startsWith($branch, '*'),
                        'name' => $matches['name'],
                        'tracking' => $matches['tracking'] ?? 'not tracking',
                        'commit' => $matches['commit'],
                    ];
                });
        });
    }

    public function stage($files, $args = []): string
    {
        $args = array_merge(array_values($files), array_values($args));

        return $this->repo->run('add', $args);
    }

    public function unstage($files, $args = []): string
    {
        $args = array_merge(array_values($files), array_values($args), ['--staged']);

        return $this->repo->run('restore', $args);
    }

    public function discard($files): string
    {
        // First, delete any files that aren't in the git index
        foreach ($files as $key => $file) {
            try {
                $result = $this->repo->run('ls-files', ['--error-unmatch', $file]);
            } catch (ProcessException $e) {
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $e->getMessage()) as $result) {
                    if (Str::startsWith($result, 'error:')) {
                        break;
                    }
                }
            }

            // XXX: This is gonna be real slow for large sets
            $in_index = ! Str::startsWith($result, 'error:');
            if ($in_index) {
                continue;
            }

            unlink($file);
            unset($files[$key]);
        }

        // Second, restore the state of changed files
        return $this->repo->run('checkout', $files);
    }

    public function remove($files, $args = []): string
    {
        $args = array_merge(array_values($files), array_values($args), []);

        return $this->repo->run('rm', $args);
    }

    public function commit($message): string
    {
        Cache::forget('gitamic.status');

        return $this->repo->run('commit', ['-m ' . addslashes($message)]);
    }

    public function push(): string
    {
        Cache::forget('gitamic.status');

        return $this->repo->run('push');
    }

    public function pull(): string
    {
        Cache::forget('gitamic.status');
        Cache::forget('gitamic.branches');

        return $this->repo->run('pull');
    }

    public function upToDate(): bool
    {
        $status = $this->status();

        return ! Str::contains($status, self::STATUS_BEHIND)
            && ! Str::contains($status, self::STATUS_AHEAD);
    }

    public function ahead(): bool
    {
        return Str::contains($this->status(), self::STATUS_AHEAD);
    }

    public function behind(): bool
    {
        return Str::contains($this->status(), self::STATUS_BEHIND);
    }

    public function diverged(): bool
    {
        return Str::contains($this->status(), self::STATUS_DIVERGED);
    }

    public function status(): string
    {
        return Cache::remember('gitamic.status', config('gitamic.cache.ttl'), function() {
            $this->fetchAll();

            $status = $this->repo->run('status');

            $status_array = preg_split("/((\r?\n)|(\r\n?))/", $status);

            return implode("\r\n", array_slice($status_array, 1, 2));
        });
    }

    protected function fetchAll(): void
    {
        $this->repo->run('fetch', ['--all']);
    }

    protected function determineChangeType(GitFile $file): string
    {
        // XXX: Would this be better as a match (PHP8)?
        switch (true) {
            case $file->isChangeMode():
                return 'F';
            case $file->isCreation():
                return 'A';
            case $file->isDeletion():
                return 'D';
            case $file->isModification():
                return 'M';
            case $file->isRename():
                return 'R';
        }

        return '-';
    }

    protected function getFileDetails($file, $id): Collection
    {
        $change = 'A';
        $relative_path = $file;

        if ($file instanceof GitFile) {
            $relative_path = $file->getName();
            $change = $this->determineChangeType($file);
        } else if (! is_string($file)) {
            throw new InvalidArgumentException('$file must a string or an instance of ' . GitFile::class);
        }

        $path = base_path($relative_path);
        $is_deleted = ! File::exists($path);
        $file = File::name($path);
        $extension = '.' . File::extension($path);
        $last_modified = $is_deleted ? 'unknown' : Carbon::createFromTimestamp(File::lastModified($path))->format('Y-m-d H:i:s');
        $type = $is_deleted ? 'unknown' : File::type($path);
        $size = $is_deleted ? 'unknown' : File::size($path);
        $is_content = ! empty($file) && Str::startsWith($path, app('filesystems.paths.content'));
        $entry_path = Str::replaceFirst('content' . DIRECTORY_SEPARATOR, '', $relative_path);

        // Replace taxonomy term path with the real deal
        $entry_path = preg_replace('#taxonomies/(.*?)/(.*)\.yaml#', 'taxonomies/$1/terms/$2/default', $entry_path);

        $clean_path = Str::replaceLast($extension, '', $entry_path);
        $edit_url = null;

        if ($is_content) {
            $edit_url = implode(DIRECTORY_SEPARATOR, [config('statamic.cp.route'), $clean_path]);
        }

        $collection = explode(DIRECTORY_SEPARATOR, $entry_path)[1] ?? null;

        $is_entry = false;
        try {
            /** @var Entry $entry */
            $entry = Stache::store("entries::{$collection}")->makeItemFromFile($path, File::get($path));
            $is_entry = true;
            $edit_url = $entry->editUrl();
        } catch (Exception | ErrorException | Error $e) {
            // Something went wrong, so it's not an entry
        }

        return collect(compact(
            'id', 'relative_path', 'path', 'last_modified', 'type', 'size', 'is_content', 'is_entry', 'edit_url',
            'is_deleted', 'change',
        ));
    }
}
