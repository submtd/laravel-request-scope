<?php

namespace Submtd\LaravelRequestScope\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Submtd\LaravelRequestScope\ColumnNameSanitizer;
use Submtd\LaravelRequestScope\Services\FilterParser;

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

    protected function parseFilters(): void {
        if (!$filters = request()->get(config('laravel-request-scope.filterParameter', 'filter'))) {
            return;
        }
        if (!is_array($filters)) {
            return;
        }

        $filters = FilterParser::parse($filters);

        $filters->each(function ($parsedFilters, $field) {
            $column = ColumnNameSanitizer::sanitize($field);

            $this->builder->where(function ($query) use ($column, $parsedFilters) {
                foreach ($parsedFilters as $parsed) {
                    $query->orWhere($column, $parsed['operator'], $parsed['value']);
                }
            });
        });
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
            ColumnNameSanitizer::sanitize($sort);
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
            $table = ColumnNameSanitizer::sanitize($table);
            foreach (explode(',', $fields) as $field) {
                $field = ColumnNameSanitizer::sanitize($field);
                $select[] = $table . '.' . $field;
            }
        }
        $this->builder->select($select);
    }
}
