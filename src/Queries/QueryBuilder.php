<?php

namespace JoshuaMc1\Database\Queries;

use JoshuaMc1\Database\Models\BaseModel;
use PDO;

class QueryBuilder
{
    protected PDO $pdo;
    protected string $table;
    protected array $conditions = [];
    protected array $fields = ['*'];
    protected array $bindings = [];
    protected ?string $modelClass = null;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select(array $fields = ['*']): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->conditions[] = new Conditions($column, $operator, $value);
        $this->bindings[] = $value;
        return $this;
    }

    public function setModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildQuery();
        $statement = $this->pdo->prepare($sql);

        foreach ($this->bindings as $index => $value) {
            $statement->bindValue($index + 1, $value);
        }

        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($this->modelClass) {
            return array_map([$this->modelClass, 'createInstance'], $rows);
        }

        return $rows;
    }

    public function first(): ?BaseModel
    {
        $sql = $this->buildQuery() . " LIMIT 1";
        $statement = $this->pdo->prepare($sql);

        foreach ($this->bindings as $index => $value) {
            $statement->bindValue($index + 1, $value);
        }

        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($result && $this->modelClass && method_exists($this->modelClass, 'createInstance')) {
            return $this->modelClass::createInstance($result);
        }

        return null;
    }

    public function insert(array $data): int
    {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

        $sql = "INSERT INTO {$this->table} ($keys) VALUES ($placeholders)";
        $statement = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();

        return $this->pdo->lastInsertId();
    }

    public function update(array $data, array $conditions = []): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);

        if (!empty($conditions)) {
            $conditionsStr = [];
            foreach ($conditions as $column => $value) {
                $conditionsStr[] = "$column = :$column";
            }
            $sql .= " WHERE " . implode(' AND ', $conditionsStr);
        }

        $statement = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        foreach ($conditions as $column => $value) {
            $statement->bindValue(":$column", $value);
        }

        $statement->execute();

        return $statement->rowCount();
    }

    public function delete(): int
    {
        if (empty($this->conditions)) {
            throw new \Exception("No conditions provided for delete operation.");
        }

        $conditions = array_map(fn($c) => $c->toSql(), $this->conditions);
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $conditions);

        $statement = $this->pdo->prepare($sql);

        foreach ($this->conditions as $condition) {
            $statement->bindValue($condition->getPlaceholder(), $condition->getValue());
        }

        $statement->execute();

        return $statement->rowCount();
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->conditions[] = "ORDER BY $column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->conditions[] = "LIMIT $limit";
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->conditions[] = "OFFSET $offset";
        return $this;
    }

    public function join(string $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        $this->conditions[] = "JOIN $table ON $firstColumn $operator $secondColumn";
        return $this;
    }

    public function groupBy(string $columns): self
    {
        $this->conditions[] = "GROUP BY $columns";
        return $this;
    }

    public function having(string $column, string $operator, $value): self
    {
        $this->conditions[] = "HAVING $column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if (!empty($this->conditions)) {
            $conditions = array_map(fn($c) => $c->toSql(), $this->conditions);
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $statement = $this->pdo->prepare($sql);

        foreach ($this->bindings as $index => $value) {
            $statement->bindValue($index + 1, $value);
        }

        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    protected function buildQuery(): string
    {
        $fields = implode(', ', $this->fields);
        $sql = "SELECT $fields FROM {$this->table}";

        if (!empty($this->conditions)) {
            $conditions = array_map(fn($c) => $c->toSql(), $this->conditions);
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return $sql;
    }

    public function pluck(string $column): array
    {
        $sql = "SELECT $column FROM {$this->table}";

        if (!empty($this->conditions)) {
            $conditions = array_map(fn($c) => $c->toSql(), $this->conditions);
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $statement = $this->pdo->prepare($sql);

        foreach ($this->bindings as $index => $value) {
            $statement->bindValue($index + 1, $value);
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function raw(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
