<?php

namespace SimonHamp\Gitamic\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use SimonHamp\Gitamic\Contracts\SiteRepository;

class GitamicApiController
{
    public function status(SiteRepository $git)
    {
        if ($git->repo()->isBare()) {
            $data = json_encode(['bare' => true, 'loaded' => true]);
            return view('gitamic::status', ['wrapper_class' => 'max-w-full', 'data' => $data]);
        }

        $unstaged = $git->getUnstagedFiles();
        $staged = $git->getStagedFiles();

        $data = [
            'unstaged' => $unstaged->all(),
            'staged' => $staged->all(),
            'meta' => [
                'unstaged_count' => $unstaged->count(),
                'staged_count' => $staged->count(),
            ],
            'up_to_date' => $git->upToDate(),
            'ahead' => $git->ahead(),
            'behind' => $git->behind(),
            'diverged' => $git->diverged(),
            'status' => $git->status(),
        ];

        if (request()->wantsJson()) {
            return response()->json($data);
        }

        $data['loaded'] = true;
        $data['bare'] = false;

        return view('gitamic::status', ['wrapper_class' => 'max-w-full', 'data' => json_encode($data)]);
    }

    public function init(): JsonResponse
    {
        app()->forgetInstance(SiteRepository::class);
        $result = shell_exec('cd ../ && git init');
        $git = app(SiteRepository::class);
        $success = $result && ! $git->repo()->isBare();
        return response()->json(['success' => $success]);
    }

    public function actions($type): JsonResponse
    {
        $method = Str::camel("get_{$type}_actions");
        return response()->json($this->{$method}());
    }

    public function doAction(SiteRepository $git, $type): JsonResponse
    {
        $action = request()->input('action');
        $selections = request()->input('selections');

        $files = $git->getFilesOfType($type)->only($selections)->map(function ($file) {
            return $file->get('path');
        });

        return response()->json(['action' => $git->{$action}($files->all())]);
    }

    public function commit(SiteRepository $git): JsonResponse
    {
        $result = $git->commit(request()->input('commit_message'));

        return response()->json(['result' => $result]);
    }

    public function push(SiteRepository $git): JsonResponse
    {
        $result = $git->push();

        return response()->json(['result' => $result]);
    }

    public function pull(SiteRepository $git): JsonResponse
    {
        $result = $git->pull();

        return response()->json(['result' => $result]);
    }

    protected function getUnstagedActions(): array
    {
        return [
            [
                'title' => 'Stage',
                'handle' => 'stage',
                'fields' => ['path'],
            ],
            [
                'title' => 'Discard',
                'handle' => 'discard',
                'confirmationText' => 'Are you sure want to discard changes to the selected files?',
                'warningText' => 'You cannot undo this!',
                'buttonText' => 'Yes, discard',
                'dangerous' => true,
                'confirm' => true,
                'fields' => ['path'],
            ],
        ];
    }

    protected function getStagedActions(): array
    {
        return [
            [
                'title' => 'Unstage',
                'handle' => 'unstage',
                'buttonText' => 'Unstage',
                'fields' => ['path'],
            ],
        ];
    }
}
