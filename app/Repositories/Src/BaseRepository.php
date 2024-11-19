<?php

namespace App\Repositories\Src;

use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    public string $orderDirection = 'desc';
    public string $orderColumn = 'id';
    protected $model;
    public $query;
    protected $take;
    public array $with = [];
    protected array $wheres = [];
    protected array $whereIns = [];
    protected array $orderBys = [];
    protected bool $hasIsActive = false;
    protected int $defaultIsActive = 1;
    protected string $primaryKey = 'id';
    protected Request $request;

    public function __construct()
    {
        $this->makeModel();
        $this->enableQueryLogIfDebug();
        $this->request = app('request');
    }

    private function enableQueryLogIfDebug(): void
    {
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
    }

    private function applyDefaultActiveStatus(): void
    {
        if ($this->hasIsActive) {
            $this->where('is_active', $this->defaultIsActive);
        }
    }

    public function makeModel(): Model
    {
        if (!$this->model()) {
            throw new Exception('Model not set in repository');
        }

        $model = app()->make($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of ".Model::class);
        }

        return $this->model = $model;
    }

    abstract public function model();

    public function where(string|Closure $column, string $operator = null, string|int $value = null): BaseRepository
    {
        $this->wheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    public function count(): int
    {
        $this->newQuery()->setRelation()->setClauses();

        $model = $this->query->count();

        $this->unsetClauses();

        return $model;
    }

    protected function setClauses()
    {
        $this->applyDefaultActiveStatus();

        foreach ($this->wheres as $where) {
            $this->query->where($where['column'], $where['operator'], $where['value']);
        }

        foreach ($this->whereIns as $whereIn) {
            $this->query->whereIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->orderBys as $orders) {
            $this->query->orderBy($orders['column'], $orders['direction']);
        }

        if (!$this->request->has('sort') && empty($this->orderBys)) {
            $this->query->orderBy($this->orderColumn, $this->orderDirection);
        }

        if ($this->take) {
            $this->query->take($this->take);
        }

        return $this;
    }

    public function whereIn(string $column, mixed $values): BaseRepository
    {
        $values = is_array($values) ? $values : [$values];

        $this->whereIns[] = compact('column', 'values');

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): BaseRepository
    {
        $this->orderBys[] = compact('column', 'direction');

        return $this;
    }

    protected function setRelation()
    {
        $this->query->with($this->with);

        return $this;
    }

    public function with(mixed $relations): BaseRepository
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    public function newQuery()
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    protected function unsetClauses()
    {
        $this->wheres = [];
        $this->whereIns = [];
        $this->orderBys = [];
        $this->take = null;

        return $this;
    }

    public function first(array $columns = ['*'])
    {
        $this->newQuery()->setRelation()->setClauses();

        $model = $this->query->first($columns);

        $this->unsetClauses();

        return $model;
    }

    public function get(array $columns = ['*']): Collection|array
    {
        $this->newQuery();

        $models = method_exists($this, 'filter') ? $this->filter($this->query) : $this->query;

        $this->setClauses()->setRelation();

        $models = $models->get($columns);

        $this->unsetClauses();

        return $models;
    }

    public function paginate(
        int $limit = 25,
        array $columns = ['*'],
        string $pageName = 'page',
        int $page = null
    ): LengthAwarePaginator {
        $this->newQuery();

        $limit = request('limit') ?? $limit;

        $limit = min($limit, 100);

        $models = method_exists($this, 'filter') ? $this->filter($this->query) : $this->query;

        $this->setRelation()->setClauses();

        $models = $models->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        return $models;
    }

    public function createMultiple(array $data): Collection
    {
        $models = new Collection();

        foreach ($data as $item) {
            $models->push($this->create($item));
        }

        return $models;
    }

    public function create(array $data): Model
    {
        $this->unsetClauses();

        return $this->model->create($data);
    }

    public function deleteById(mixed $id): bool
    {
        $this->unsetClauses();

        return $this->find($id)->delete();
    }

    public function delete(): mixed
    {
        $this->newQuery()->setClauses();

        $result = $this->query->delete();

        $this->unsetClauses();

        return $result;
    }

    public function find(mixed $value, array $columns = ['*']): Model|Collection
    {
        $this->unsetClauses();

        $this->newQuery()->setRelation();

        $model = method_exists($this, 'filter') ? $this->filter($this->query) : $this->query;

        return $model->where($this->primaryKey, $value)->firstOrFail($columns);
    }

    public function findOrFail($id, $columns = ['*'])
    {
        $this->newQuery()->setRelation()->setClauses();

        $model = $this->query->findOrFail($id, $columns);

        $this->unsetClauses();

        return $model;
    }

    public function firstOrFail(array $columns = ['*'])
    {
        $this->newQuery()->setRelation()->setClauses();

        $model = $this->query->firstOrFail($columns);

        $this->unsetClauses();

        return $model;
    }

    public function deleteMultipleById(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    public function updateById(mixed $id, array $data, array $options = []): Model|Collection
    {
        $this->unsetClauses();

        $model = $this->find($id);

        $model->update($data, $options);

        $model->refresh();

        return $model;
    }

    public function limit(int $limit): BaseRepository
    {
        $this->take = $limit;

        return $this;
    }

    public function hasIsActive(bool $value = false): BaseRepository
    {
        $this->hasIsActive = $value;

        return $this;
    }

    public function setDefaultIsActive(int $value = 1): BaseRepository
    {
        $this->defaultIsActive = $value;

        return $this;
    }

    public function setPrimaryKey(string $key = 'id'): BaseRepository
    {
        $this->primaryKey = $key;

        return $this;
    }
}
