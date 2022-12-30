<?php

namespace Submtd\LaravelRequestScope\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;
use Submtd\LaravelRequestScope\ColumnNameSanitizer;
use Submtd\LaravelRequestScope\Services\FilterParser;

class RequestScope implements Scope
{
    protected Builder $builder;
    protected Model $model;

    /**
     * {@inheritDoc}
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
    protected function parsePagination(): void
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
        $filters = request()->input(config('laravel-request-scope.filterParameter', 'filter'));
        if (empty($filters) || ! is_array($filters)) {
            return;
        }

        if (! empty($filters['tags'])) {
            $this->processTagFilters($filters);
        }

        $filters = FilterParser::parse($filters);

        $filters->each(function ($parsedFilters, $field) {
            $column = ColumnNameSanitizer::sanitize($field);

            $this->builder->where(static function (Builder $query) use ($column, $parsedFilters) {
                foreach ($parsedFilters as $parsed) {
                    $value = $parsed['value'];
                    $column = $query->qualifyColumn($column);
                    $method = Str::startsWith($parsed['operator'], 'where') ?
                        $parsed['operator'] :
                        Str::camel('where_'.$parsed['operator']);

                    // Scopes take priority over everything else.
                    if ($query->hasNamedScope($parsed['operator'])) {
                        $query->{$parsed['operator']}($value);
                    } // Fall back to direct method calls on the query.
                    elseif (method_exists($query, $method) || method_exists($query->toBase(), $method)) {
                        $query->{$method}($column, $value);
                    } else {
                        $query->orWhere($column, $parsed['operator'], $value);

                        // add all null rows in case ne| is used and value !== null.
                        // add all null rows in case eq|null
                        if(
                            (in_array($parsed['operator'], [ '<>', '!=' ]) && strtolower($value) !== 'null')
                            || (in_array($parsed['operator'], [ '=' ]) && strtolower($value) === 'null')
                        ) {
                            $query->orWhereNull($column);

                        // add all not null rows in case ne|null is used
                        } else if ( in_operator($parsed['operator'], [ '<>', '!=' ]) && strtolower($value) === 'null' ){
                            $query->orWhereNotNull($column);
                        }
                    }
                }
            });
        });
    }

    protected function processTagFilters(&$filters): void
    {
        foreach ($filters['tags'] as $item) {
            $tags = explode(',', $item['name'] ?? null);
            if ($vocabulary = ($item['vocabulary'] ?? null)) {
                if ($this->model->hasNamedScope('withAllTags')) {
                    $this->builder->withAllTags($tags, $vocabulary);
                }
            } elseif ($this->model->hasNamedScope('withAllTagsOfAnyType')) {
                $this->builder->withAllTagsOfAnyType($tags);
            }
        }
        unset($filters['tags']);
    }

    protected function parseIncludes(): void
    {
        $includes = request()->input(config('laravel-request-scope.includeParameter', 'include'));
        foreach (explode(',', $includes) as $include) {
            if (! $include) {
                continue;
            }
            $this->builder->with($include);
        }
    }

    protected function parseSorts(): void
    {
        if (! $sorts = request()->input(config('laravel-request-scope.sortParameter', 'sort'))) {
            return;
        }
        $sorts = explode(',', $sorts);
        foreach ($sorts as $sort) {
            ColumnNameSanitizer::sanitize($sort);
            if ($sort[0] === '-') {
                $this->builder->orderBy(ltrim($sort, '-'), 'desc');
            } else {
                $this->builder->orderBy($sort);
            }
        }
    }

    protected function parseFields(): void
    {
        if (! $fields = request()->input(config('laravel-request-scope.fieldsParameter', 'fields'))) {
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
