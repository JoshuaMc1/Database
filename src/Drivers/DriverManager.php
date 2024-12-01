<?php

namespace JoshuaMc1\Database\Drivers;

use JoshuaMc1\Database\Config\ConfigLoader;

class DriverManager
{
    private static ?Driver $driver = null;

    public static function getDriver(): Driver
    {
        if (!self::$driver) {
            self::$driver = self::createDefaultDriver();
            self::$driver->connect();
        }

        return self::$driver;
    }

    public static function createDefaultDriver(): Driver
    {
        $configPath = sprintf('%s/../../../../config/database.php', __DIR__);
        $config = ConfigLoader::load($configPath);

        $defaultDriver = $config['default'] ?? null;

        if (!$defaultDriver || !isset($config['connections'][$defaultDriver])) {
            throw new \Exception("Default driver configuration not found.");
        }

        return match ($defaultDriver) {
            'mysql' => new MySqlDriver(),
            'sqlite' => new SqliteDriver(),
            'pgsql' => new PgSqlDriver(),
            default => throw new \Exception("Unsupported driver: $defaultDriver"),
        };
    }
}
