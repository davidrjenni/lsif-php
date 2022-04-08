<?php

declare(strict_types=1);

namespace LsifPhp\Git;

use Exception;

final class GitException extends Exception
{
    public function __construct(string $cmd, string $error)
    {
        parent::__construct("Error while executing {$cmd}: {$error}");
    }
}
