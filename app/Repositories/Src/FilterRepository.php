<?php

namespace App\Repositories\Src;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait FilterRepository
{
    /**
     * The request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * @var array
     */
    protected array $filterField = [];

    /**
     *
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->initializeFilterFields();
    }

    /**
     * Specify filer fields.
     *
     * @return mixed
     */
    abstract public function defineFilterFields(): mixed;

    /**
     * @return $this
     */
    public function initializeFilterFields(): static
    {
        $this->filterField = $this->defineFilterFields();

        return $this;
    }

    /**
     * @param  mixed  $query  Query.
     *
     * @return Builder Builder.
     */
    public function filter(mixed $query = null): Builder
    {
        $query = $query ?? $this->model->newQuery();

        $this->initializeFilterFields();

        if (empty($this->filterField)) {
            return $query;
        }

        /* Ignore "with" query param in GET method */
        $query = $this->includeWith($query);
        $query = $this->includeWithCounts($query);

        if (!$this->request->isMethod('GET')) {
            return $query;
        }

        $query = $this->applySorts($query);
        return $this->buildSearchParams($query);
    }

    /**
     * @param  mixed  $builder  Builder.
     *
     * @return mixed Builder.
     */
    protected function buildSearchParams(mixed $builder): mixed
    {
        foreach ($this->request->all() as $key => $value) {
            $this->buildOperatorParams($builder, $key, $value);
        }

        return $builder;
    }

    protected function buildOperatorParams(
        mixed $builder,
        mixed $key,
        mixed $value = null,
        mixed $relation = null
    ): mixed {

        if (!is_null($value) && auth()->check()) {
            $value = $this->replaceCurrentUserPlaceholder($value);
        }

        $filterOperators = [
            ''             => '=',
            '_not'         => '!=',
            '_gt'          => '>',
            '_lt'          => '<',
            '_gte'         => '>=',
            '_lte'         => '<=',
            '_like'        => 'LIKE',
            '_in'          => true,
            '_notIn'       => true,
            '_isNull'      => true,
            '_isNotNull'   => true,
            '_between'     => true,
            '_notBetween'  => true,
            '_date'        => true,
            '_dateBetween' => true,
            '_month'       => true,
            '_day'         => true,
            '_year'        => true,
            '_time'        => true,
        ];

        foreach ($filterOperators as $op_key => $op_type) {
            $key = strtolower($key);
            $op_key = strtolower($op_key);

            if (Str::endsWith($key, $op_key) === false && $op_key != '') {
                continue;
            }

            $column_name = $this->getColumnName($key, $op_key, $relation);
            if (!$column_name) {
                continue;
            }

            $this->applyFilter($builder, $column_name, $value, $op_key, $op_type);
        }

        return $builder;
    }

    protected function replaceCurrentUserPlaceholder($value)
    {
        if (!is_array($value) && Str::contains($value, 'current_user')) {
            return Str::replace('current_user', auth()->id(), $value);
        }

        return $value;
    }

    protected function getColumnName($key, $op_key, $relation)
    {
        $column_name = Str::replaceLast($op_key, '', $key);

        if (is_null($relation)) {
            if ($column_name == 'with' || !isset($this->filterField[$column_name])) {
                return false;
            }
            return $this->filterField[$column_name];
        }
        if (!isset($this->filterField['with'][$relation][$column_name])) {
            return false;
        }
        return $this->filterField['with'][$relation][$column_name];

    }

    protected function applyFilter($builder, $column_name, $value, $op_key, $op_type)
    {
        switch ($op_key) {
            case '':
                $builder->where($column_name, $value);
                break;
            case '_in':
                $builder->whereIn($column_name, explode(',', $value));
                break;
            case '_notIn':
                $builder->whereNotIn($column_name, explode(',', $value));
                break;
            case '_isNull':
                $builder->whereNull($column_name);
                break;
            case '_isNotNull':
                $builder->whereNotNull($column_name);
                break;
            case '_like':
                $builder->orWhere($column_name, 'LIKE', "%{$value}%");
                break;
            case '_between':
                $builder->whereBetween($column_name, explode(',', $value));
                break;
            case '_notBetween':
                $builder->whereNotBetween($column_name, explode(',', $value));
                break;
            case '_date':
                $builder->whereDate($column_name, $value);
                break;
            case '_dateBetween':
                $date = explode(',', $value);
                $builder->whereDate($column_name, '>=', $date[0])->whereDate($column_name, '<=', $date[1]);
                break;
            case '_month':
                $builder->whereMonth($column_name, $value);
                break;
            case '_day':
                $builder->whereDay($column_name, $value);
                break;
            case '_year':
                $builder->whereYear($column_name, $value);
                break;
            case '_time':
                $builder->whereTime($column_name, '=', $value);
                break;
            default:
                $builder->where($column_name, $op_type, $value);
                break;
        }
    }

    /**
     * @param  mixed  $builder  Builder.
     *
     * @return mixed Builder.
     */
    public function includeWith(mixed $builder): mixed
    {
        if (!$payload = $this->request->with) {
            return $builder;
        }

        $payload = explode('|', $payload);
        foreach ($payload as $data) {
            $explode_relation_and_condition = explode('=>', $data);
            $relation = $explode_relation_and_condition[0];
            $conditions = isset($explode_relation_and_condition[1]) ? explode(
                '/',
                $explode_relation_and_condition[1]
            ) : null;

            if (!isset($this->filterField['with'][$relation]['relation'])) {
                continue;
            }
            $with = $this->filterField['with'][$relation]['relation'];

            if (!method_exists($this->model, $with)) {
                continue;
            }

            if (is_null($conditions)) {
                $builder->with($with);
            } else {
                $builder->whereHas($with, function ($query) use ($relation, $conditions) {
                    foreach ($conditions as $condition) {
                        $condition = explode('=', $condition);
                        $this->buildOperatorParams($query, $condition[0] ?? null, $condition[1] ?? null, $relation);
                    }
                })->with($with);
            }
        }

        return $builder;
    }

    /**
     * @param  mixed  $builder  Builder.
     *
     * @return mixed
     */
    protected function includeWithCounts(mixed $builder): mixed
    {
        if (!$count_info = $this->request->with_count) {
            return $builder;
        }

        $counters = explode(',', $count_info);

        foreach ($counters as $counter) {
            if (!isset($this->filterField['with'][$counter])) {
                continue;
            }

            $with = $this->filterField['with'][$counter]['relation'];
            if (method_exists($this->model, $with)) {
                $builder->withCount($with);
            }
        }

        return $builder;
    }

    /**
     * @param  mixed  $builder  Builder.
     *
     * @return mixed
     */
    protected function applySorts(mixed $builder): mixed
    {
        $sorts = explode(',', $this->request->sort);

        foreach ($sorts as $sort) {
            $explode_sort = explode(':', $sort);
            if (!($explode_sort && count($explode_sort) == 2)) {
                continue;
            }
            $key = trim($explode_sort[0]);
            $value = trim($explode_sort[1]);

            if (!isset($this->filterField[$key])) {
                continue;
            }
            if ($key == 'with') {
                continue;
            }

            $key = $this->filterField[$key];

            $builder->orderBy($key, $value);

        }

        return $builder;
    }
}
