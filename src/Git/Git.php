<?php

declare(strict_types=1);

namespace LsifPhp\Git;

use function exec;
use function implode;
use function substr;

final class Git
{
    /** Returns the version of the Git repository in the current directory. */
    public function version(): string
    {
        $version = $this->git('tag', '-l', '--points-at', 'HEAD');
        if ($version !== '') {
            return $version;
        }
        $sha = $this->git('rev-parse', 'HEAD');
        return substr($sha, 0, 12);
    }

    private function git(string ...$args): string
    {
        $cmd = 'git ' . implode(' ', $args);
        $output = [];
        $result = exec($cmd, $output);
        if ($result === false) {
            throw new GitException($cmd, $output[0] ?? '');
        }
        return $result;
    }
}
