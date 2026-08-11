<?php

namespace App\Support\Repositories;

use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Eloquent implementation of the generic repository contract.
 *
 * Domain repositories (e.g. ProgramRepository) extend this for the common
 * CRUD path and add domain-specific query methods on top. This class is the
 * only place in a domain module allowed to build Eloquent queries directly —
 * Services never touch the query builder themselves (ADR-0001 §2).
 *
 * @template TModel of Model
 */
abstract class EloquentRepository implements RepositoryInterface
{
    /**
     * @param  class-string<TModel>  $modelClass
     */
    public function __construct(protected string $modelClass) {}

    protected function newQuery()
    {
        return $this->modelClass::query();
    }

    public function find(string $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findOrFail(string $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    public function all(): Collection
    {
        return $this->newQuery()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()->paginate($perPage);
    }

    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
