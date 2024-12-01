<?php

namespace JoshuaMc1\Database\Drivers;

use PDO;

class PgSqlDriver extends Driver
{
    public function connect(): void
    {
        $config = $this->getConfig('pgsql');

        $dsn = sprintf(
            'pgsql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['database'],
            $config['charset']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password']);
    }
}
