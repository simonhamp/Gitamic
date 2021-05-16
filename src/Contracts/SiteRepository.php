<?php

namespace SimonHamp\Gitamic\Contracts;

use Gitonomy\Git\Repository;
use Illuminate\Support\Collection;

interface SiteRepository
{
    public function repo(): Repository;

    public function getFilesOfType($type): Collection;

    public function getUnstagedFiles(): Collection;

    public function getUntrackedFiles(): Collection;

    public function getStagedFiles(): Collection;

    public function getPendingFiles(): Collection;

    public function currentBranch(): array;

    public function stage($files, $args = []): string;

    public function unstage($files, $args = []): string;

    public function discard($files): string;

    public function remove($files, $args = []): string;

    public function commit($message): string;

    public function push(): string;

    public function pull(): string;

    public function upToDate(): bool;

    public function ahead(): bool;

    public function behind(): bool;

    public function diverged(): bool;

    public function status(): string;
}
