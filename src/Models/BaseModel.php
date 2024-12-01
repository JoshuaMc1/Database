<?php

namespace JoshuaMc1\Database\Models;

use JoshuaMc1\Database\Drivers\DriverManager;
use JoshuaMc1\Database\Queries\QueryBuilder;

abstract class BaseModel
{
    protected string $table;
    protected array $attributes = [];
    protected array $original = [];

    protected array $fillable = [];
    protected array $guarded = ['*'];

    protected array $hidden = [];

    public function __construct(array $attributes = [])
    {
        $this->fillAttributes($attributes);
    }

    protected static function getDriver()
    {
        return DriverManager::getDriver();
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder(self::getDriver()->getPdo(), $instance->getTable());
    }

    public function getTable(): string
    {
        return $this->table ?? strtolower((new \ReflectionClass($this))->getShortName()) . 's';
    }

    public static function create(array $attributes): self
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    protected function fillAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }

        if (in_array($key, $this->guarded) || $this->guarded === ['*']) {
            return false;
        }

        return empty($this->fillable);
    }

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        if ($this->isFillable($key)) {
            $this->attributes[$key] = $value;
        }
    }

    public function toArray(): array
    {
        return array_filter(
            $this->attributes,
            fn($key) => !in_array($key, $this->hidden),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function save(): bool
    {
        $query = self::query();

        if (isset($this->original['id'])) {
            $query->update($this->attributes, ['id' => $this->original['id']]);
        } else {
            $id = $query->insert($this->attributes);
            $this->original['id'] = $id;
            $this->attributes['id'] = $id;
        }

        return true;
    }

    public function delete(): bool
    {
        return self::query()->delete(['id' => $this->original['id']]);
    }

    public function refresh(): void
    {
        $this->original = $this->attributes;
    }

    public function exists(): bool
    {
        return isset($this->original['id']) && !empty($this->original['id']);
    }

    public static function find($id): ?self
    {
        $instance = new static();
        $result = $instance->query()->where('id', '=', $id)->first();

        return $result ? $instance->fillAttributes($result) : null;
    }

    public static function where(string $column, string $operator, $value): QueryBuilder
    {
        return self::query()->where($column, $operator, $value);
    }

    public static function having(string $column, string $operator, $value): QueryBuilder
    {
        return self::query()->having($column, $operator, $value);
    }

    public static function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return self::query()->orderBy($column, $direction);
    }

    public static function limit(int $limit): QueryBuilder
    {
        return self::query()->limit($limit);
    }

    public static function pluck(string $column): array
    {
        return self::query()->pluck($column);
    }

    public static function get(): array
    {
        $queryResult = self::query()->get();
        $instances = [];

        foreach ($queryResult as $attributes) {
            $instance = new static();
            $instance->fillAttributes($attributes);
            $instances[] = $instance;
        }

        return $instances;
    }

    public static function findOrFail($id): self
    {
        $instance = self::find($id);

        if (!$instance) {
            throw new \Exception("Model not found with ID: $id");
        }

        return $instance;
    }

    public function updateAttributes(array $attributes): bool
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return self::query()->update($attributes, ['id' => $this->original['id']]) > 0;
    }

    public static function all(): array
    {
        $queryResult = self::query()->get();
        $instances = [];

        foreach ($queryResult as $attributes) {
            $instance = new static();
            $instance->fillAttributes($attributes);
            $instances[] = $instance;
        }

        return $instances;
    }
}
