<?php

declare(strict_types=1);

namespace LsifPhp\Composer;

use LsifPhp\File\FileReader;

use function array_values;
use function json_decode;

/** Composer provides methods to parse composer files. */
final class Composer
{

    /** Parses the composer.json file at the given project root. */
    public static function parse(string $projectRoot): Composer
    {
        $composerFile = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
        return new Composer(json_decode(FileReader::read($composerFile), true));
    }

    /** @param  array<string, mixed>  $composer */
    public function __construct(private array $composer)
    {
    }

    /** @return string[] */
    public function sourceDirs(): array
    {
        $autoloadDirs = array_values($this->composer['autoload']['psr-4'] ?? []);
        $autoloadDevDirs = array_values($this->composer['autoload-dev']['psr-4'] ?? []);
        return array_merge($autoloadDirs, $autoloadDevDirs);
    }
}
