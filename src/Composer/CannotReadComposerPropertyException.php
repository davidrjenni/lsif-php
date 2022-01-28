<?php

declare(strict_types=1);

namespace LsifPhp\Composer;

use Exception;

final class CannotReadComposerPropertyException extends Exception
{
    public function __construct(string $property)
    {
        parent::__construct("Cannot read property '{$property}' from composer.json.");
    }
}
