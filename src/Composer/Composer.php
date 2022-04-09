<?php

declare(strict_types=1);

namespace LsifPhp\Composer;

use LsifPhp\File\FileReader;

use function array_merge;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function str_starts_with;
use function substr;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/** Composer provides methods to parse composer files. */
final class Composer
{
    /** @var array<string, PkgInfo> */
    private array $deps;

    /** Parses the composer.json file at the given project root. */
    public static function parse(string $projectRoot): Composer
    {
        $composerFile = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
        $lockFile = $projectRoot . DIRECTORY_SEPARATOR . 'composer.lock';
        return new Composer(
            json_decode(FileReader::read($composerFile), true, 512, JSON_THROW_ON_ERROR),
            json_decode(FileReader::read($lockFile), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param  array<string, mixed>  $composer
     * @param  array<string, mixed>  $lock
     */
    public function __construct(private array $composer, array $lock)
    {
        $this->deps = [];

        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        foreach ($packages as $pkg) {
            $namespaces = array_keys($pkg['autoload']['psr-4'] ?? []);
            if (count($namespaces) === 0) {
                continue;
            }
            if (!isset($pkg['name']) || !is_string($pkg['name'])) {
                continue;
            }
            if (!isset($pkg['source']['reference']) || !is_string($pkg['source']['reference'])) {
                continue;
            }
            $pkg = new PkgInfo($pkg['name'], substr($pkg['source']['reference'], 0, 12));
            foreach ($namespaces as $namespace) {
                if (is_string($namespace)) {
                    $this->deps[$namespace] = $pkg;
                }
            }
        }
    }

    /** @return string[] */
    public function sourceDirs(): array
    {
        $autoloadDirs = array_values($this->composer['autoload']['psr-4'] ?? []);
        $autoloadDevDirs = array_values($this->composer['autoload-dev']['psr-4'] ?? []);
        $dirs = array_merge($autoloadDirs, $autoloadDevDirs);
        foreach ($dirs as $i => $dir) {
            if (is_array($dir)) {
                unset($dirs[$i]);
                foreach ($dir as $d) {
                    $dirs[] = $d;
                }
            }
        }
        sort($dirs);
        foreach ($dirs as $i => $dir) {
            if ($this->prefixExists($dirs, $dir)) {
                unset($dirs[$i]);
            }
        }
        return array_values($dirs);
    }

    /** @param  string[]  $dirs */
    private function prefixExists(array $dirs, string $dir): bool
    {
        foreach ($dirs as $d) {
            if ($dir !== $d && str_starts_with($dir, $d)) {
                return true;
            }
        }
        return false;
    }

    public function pkgName(): string
    {
        if (!isset($this->composer['name']) || !is_string($this->composer['name'])) {
            throw new CannotReadComposerPropertyException('name');
        }
        return $this->composer['name'];
    }

    public function dependency(string $ident): ?PkgInfo
    {
        foreach ($this->deps as $namespace => $pkg) {
            if (str_starts_with($ident, $namespace)) {
                return $pkg;
            }
        }
        return null;
    }
}
