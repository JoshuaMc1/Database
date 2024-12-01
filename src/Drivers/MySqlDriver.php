<?php

namespace JoshuaMc1\Database\Drivers;

use PDO;

class MySqlDriver extends Driver
{
    public function connect(): void
    {
        $config = $this->getConfig('mysql');

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['database'],
            $config['charset']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password']);
    }
}
