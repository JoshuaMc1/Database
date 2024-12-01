<?php

namespace JoshuaMc1\Database\Config;

class ConfigLoader
{
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("Config file not found at: $path");
        }

        return include $path;
    }
}
