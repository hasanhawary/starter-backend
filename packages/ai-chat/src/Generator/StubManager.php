<?php

namespace AiChat\Generator;

use Illuminate\Support\Facades\File;

class StubManager
{
    protected string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = dirname(__DIR__, 2).'/stubs';
    }

    public function get(string $stubName): string
    {
        $path = $this->stubsPath.'/'.$stubName.'.stub';

        if (! File::exists($path)) {
            throw new \RuntimeException("Stub file [{$stubName}] not found at [{$path}].");
        }

        return File::get($path);
    }

    public function replace(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{'.strtoupper($key).'}}', $value, $stub);
        }

        return $stub;
    }

    public function setStubsPath(string $path): void
    {
        $this->stubsPath = $path;
    }

    public function getStubsPath(): string
    {
        return $this->stubsPath;
    }
}
