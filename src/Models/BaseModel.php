<?php

namespace JoshuaMc1\Database\Models;

use JoshuaMc1\Database\Drivers\DriverManager;
use JoshuaMc1\Database\Queries\QueryBuilder;

abstract class BaseModel
{
    protected $table;
    protected $attributes = [];
    protected $original = [];

    protected $fillable = [];
    protected $guarded = ['*'];

    protected $hidden = [];

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
        $builder = new QueryBuilder(self::getDriver()->getPdo(), (new static())->getTable());
        return $builder->setModel(static::class);
    }

    public static function createInstance(array $attributes): static
    {
        $instance = new static();
        $instance->fillAttributes($attributes);
        return $instance;
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
            if ($key === 'id' || $this->isFillable($key)) {
                $this->attributes[$key] = $value;

                $this->original[$key] = $value;
            }
        }
    }

    public function isFillable(string $key): bool
    {
        if ($key === 'id') {
            return true;
        }

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
            $attributesToUpdate = array_filter(
                $this->attributes,
                fn($key) => $key !== 'id',
                ARRAY_FILTER_USE_KEY
            );

            $query->update($attributesToUpdate, ['id' => $this->original['id']]);
        } else {
            $id = $query->insert($this->attributes);
            $this->original['id'] = $id;
            $this->attributes['id'] = $id;
        }

        return true;
    }

    public function delete(): bool
    {
        if (!isset($this->original['id'])) {
            throw new \Exception("No ID found for delete operation.");
        }

        $deleted = self::query()->where('id', '=', $this->original['id'])->delete();

        if ($deleted) {
            $this->attributes = [];
            $this->original = [];
        }

        return $deleted;
    }

    public function refresh(): void
    {
        $this->original = $this->attributes;
    }

    public function exists(): bool
    {
        return isset($this->original['id']) && !empty($this->original['id']);
    }

    public static function find($id)
    {
        return self::query()->where('id', '=', $id)
            ->first();
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

    public static function findOrFail($id)
    {
        $result = self::find($id);

        if (!$result) {
            throw new \Exception("Model not found with ID {$id}");
        }

        return $result;
    }

    public function updateAttributes(array $attributes): bool
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return self::query()->update($attributes, ['id' => $this->original['id']]) > 0;
    }

    public static function select(array $columns): QueryBuilder
    {
        return self::query()->select($columns);
    }

    public static function all(): array
    {
        return self::query()->get();
    }
}
