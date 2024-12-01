<?php

namespace JoshuaMc1\Database\Drivers;

use JoshuaMc1\Database\Config\ConfigLoader;
use PDO;

abstract class Driver
{
    protected PDO $pdo;

    abstract public function connect(): void;

    protected function getConfig(string $driverName): array
    {
        $configPath = sprintf('%s/../../../../config/config.php', __DIR__);
        $config = ConfigLoader::load($configPath);

        if (!isset($config['connections'][$driverName])) {
            throw new \Exception("Configuration for driver '$driverName' not found.");
        }

        return $config['connections'][$driverName];
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
