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

    /**
     * Apply scope.
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $this->builder = $builder;
        $this->model = $model;
        $this->parsePagination();
        $this->parseFilters();
        $this->parseIncludes();
        $this->parseSorts();
        $this->parseFields();
    }

    /**
     * Parse pagination.
     * @return void
     */
    protected function parsePagination() : void
    {
        $page = (array) request()->input('page');
        // check for page[size], page[limit], or just limit
        $limit = $page['size'] ?? $page['limit'] ?? request()->input('limit') ?? config('laravel-request-scope.defaultCollectionLimit', 50);
        // keep ridiculous limits away
        if ($limit > config('laravel-request-scope.maxCollectionLimit', 1000)) {
            $limit = config('laravel-request-scope.maxCollectionLimit', 1000);
        }
        // check for page[offset] or just offset
        $offset = $page['offset'] ?? request()->input('offset') ?? 0;
        // check for page[number] and replace offset with number * limit
        if (isset($page['number'])) {
            $offset = $page['number'] * $limit;
        }
        // add to builder
        $this->builder->limit($limit);
        $this->builder->offset($offset);
    }

    protected function parseFilters(): void
    {
        if (!$filters = request()->input(config('laravel-request-scope.filterParameter', 'filter'))) {
            return;
        }
        if (!is_array($filters)) {
            return;
        }
        if (isset($filters['tags'])) {
            foreach ($filters['tags'] as $item) {
                $tags = explode(',', $item['name'] ?? null);
                if ($vocabulary = $item['vocabulary'] ?? null) {
                    if ($this->model->hasNamedScope('withAllTags')) {
                        $this->builder->withAllTags($tags, $vocabulary);
                    }
                } else {
                    if ($this->model->hasNamedScope('withAllTagsOfAnyType')) {
                        $this->builder->withAllTagsOfAnyType($tags);
                    }
                }
            }
            unset($filters['tags']);
        }
        $filters = FilterParser::parse($filters);

        $filters->each(function ($parsedFilters, $field) {
            $column = ColumnNameSanitizer::sanitize($field);

            $this->builder->where(function ($query) use ($column, $parsedFilters) {
                foreach ($parsedFilters as $parsed) {
                    if (method_exists(\Illuminate\Database\Query\Builder::class, $parsed['operator'])){
                        $query->{$parsed['operator']}($column, $parsed['value']);
                    }
                    else{
                        $query->orWhere($column, $parsed['operator'], $parsed['value']);
                    }
                }
            });
        });
    }

    protected function parseIncludes()
    {
        $includes = request()->input(config('laravel-request-scope.includeParameter', 'include'));
        foreach (explode(',', $includes) as $include) {
            if (!$include) {
                continue;
            }
            $this->builder->with($include);
        }
    }

    protected function parseSorts()
    {
        if (!$sorts = request()->input(config('laravel-request-scope.sortParameter', 'sort'))) {
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
        if (!$fields = request()->input(config('laravel-request-scope.fieldsParameter', 'fields'))) {
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
