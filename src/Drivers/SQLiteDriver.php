<?php

namespace JoshuaMc1\Database\Drivers;

use PDO;

class SQLiteDriver extends Driver
{
    public function connect(): void
    {
        $config = $this->getConfig('sqlite');

        $dsn = sprintf('sqlite:%s', $config['database']);

        $this->pdo = new PDO($dsn);
    }
}
