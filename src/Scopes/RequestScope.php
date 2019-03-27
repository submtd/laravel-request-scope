<?php

namespace Submtd\LaravelRequestScope\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestScope implements Scope
{
    protected $builder;
    protected $model;

    public function apply(Builder $builder, Model $model)
    {
        $this->builder = $builder;
        $this->model = $model;
        $this->parseFilters();
        $this->parseIncludes();
        $this->parseSorts();
        $this->parseFields();
    }

    protected function parseFilters()
    {
        if (!$filters = request()->get(config('laravel-request-scope.filterParameter', 'filter'))) {
            return;
        }
        if (!is_array($filters)) {
            return;
        }
        foreach ($filters as $field => $filter) {
            $this->parseFilter($field, $filter);
        }
    }

    protected function parseFilter($field, $filter)
    {
        $filters = explode(',', $filter);
        $this->builder->where(function ($query) use ($field, $filters) {
            foreach ($filters as $filter) {
                $parsed = $this->parseOperator($filter);
                $query->orWhere($field, $parsed['operator'], $parsed['value']);
            }
        });
    }

    protected function parseOperator($filter)
    {
        if (!Str::contains($filter, config('laravel-request-scope.operatorSeparator', '|'))) {
            $filter = config('laravel-request-scope.defaultOperator', 'eq') . config('laravel-request-scope.operatorSeparator', '|') . $filter;
        }
        $operator = Str::before($filter, config('laravel-request-scope.operatorSeparator', '|'));
        $value = Str::after($filter, config('laravel-request-scope.operatorSeparator', '|'));
        switch ($operator) {
            case config('laravel-request-scope.lessThanOperator', 'lt'):
                return [
                    'operator' => '<',
                    'value' => $value,
                ];
            case config('laravel-request-scope.lessThanOrEqualOperator', 'lte'):
                return [
                    'operator' => '<=',
                    'value' => $value,
                ];
            case config('laravel-request-scope.greaterThanOperator', 'gt'):
                return [
                    'operator' => '>',
                    'value' => $value,
                ];
            case config('laravel-request-scope.greaterThanOrEqualOperator', 'gte'):
                return [
                    'operator' => '>=',
                    'value' => $value,
                ];
            case config('laravel-request-scope.notEqualOperator', 'ne'):
                return [
                    'operator' => '<>',
                    'value' => $value,
                ];
            case config('laravel-request-scope.betweenOperator', 'bt'):
                return [
                    'operator' => 'between',
                    'value' => [
                        Str::before($value, config('laravel-request-scope.betweenSeparator', ';')),
                        Str::after($value, config('laravel-request-scope.betweenSeparator', ';')),
                    ],
                ];
            case config('laravel-request-scope.likeOperator', 'like'):
                return [
                    'operator' => 'like',
                    'value' => '%' . $value . '%',
                ];
            case config('laravel-request-scope.startsWithOperator', 'sw'):
                return [
                    'operator' => 'like',
                    'value' => $value . '%',
                ];
            case config('laravel-request-scope.endsWithOperator', 'ew'):
                return [
                    'operator' => 'like',
                    'value' => '%' . $value,
                ];
            case config('laravel-request-scope.equalOperator', 'eq'):
                return [
                    'operator' => '=',
                    'value' => $value,
                ];
        }
    }

    protected function parseIncludes()
    {
        $includes = request()->get(config('laravel-request-scope.includeParameter', 'include'));
        foreach (explode(',', $includes) as $include) {
            if (!$include) {
                continue;
            }
            $this->builder->with($include);
        }
    }

    protected function parseSorts()
    {
        if (!$sorts = request()->get(config('laravel-request-scope.sortParameter', 'sort'))) {
            return;
        }
        $sorts = explode(',', $sorts);
        foreach ($sorts as $sort) {
            if ($sort[0] == '-') {
                $this->builder->orderBy(ltrim($sort, '-'), 'desc');
            } else {
                $this->builder->orderBy($sort);
            }
        }
    }

    protected function parseFields()
    {
        if (!$fields = request()->get(config('laravel-request-scope.fieldsParameter', 'fields'))) {
            return;
        }
        $select = [];
        foreach ($fields as $table => $fields) {
            foreach (explode(',', $fields) as $field) {
                $select[] = $table . '.' . $field;
            }
        }
        $this->builder->select($select);
    }
}
