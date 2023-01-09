<?php

declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class FilterParser.
 *
 * Parses a set of string filters into a usable array. This is meant to be a
 * generic parser that can be used outside Eloquent or a Query Builder context.
 * This means the values here should not necessarily be tied to query methods.
 */
class FilterParser
{
    /**
     * Escape a string for use with LIKE.
     *
     * Some characters have a special meaning in LIKE statements and need to be
     * escaped.
     *
     * @param string $string
     *   The string to escape.
     *
     * @return string
     *   The escaped string.
     */
    protected static function escapeLike(string $string): string
    {
        return addcslashes($string, '\\%_');
    }

    /**
     * Check if the filter was simple.
     *
     * A simple filter is missing an operator.
     *
     * @param string $filter
     *   The filter to check.
     *
     * @return bool
     *   Returns TRUE if the filter is simple.
     */
    protected static function isSimpleFilter(string $filter): bool
    {
        return ! Str::contains($filter, config('laravel-request-scope.operatorSeparator', '|'));
    }

    /**
     * Parse the filters.
     *
     * Supported filters:
     * 5 - Equals 5
     * eq|5 - Equals 5
     * lt|5 - Less than 5
     * lte|5 - Less than or equal to 5
     * gt|5 - Greater than 5
     * gte|5 - Greater than or equal to 5
     * ne|5 - Not equal to 5
     * bt|4;6 - Between 4 and 6
     * like|apple - Match apple anywhere
     * sw|apple - Match apple at the start
     * ew|apple - Match apple at the end.
     *
     * @param Collection|array $rawFilters
     *   An array or Collection of filters to parse.
     *
     * @return Collection
     *   The parsed filters.
     */
    public static function parse($rawFilters): Collection
    {
        $ret = [];
        foreach ($rawFilters as $field => $grouped_filter) {
            $split_filters = explode(config('laravel-request-scope.filterSeparator', ','), (string)$grouped_filter);
            foreach ($split_filters as $individual_filter) {
                $ret[$field][] = self::parseOperator($individual_filter);
            }
        }

        return collect($ret);
    }

    /**
     * Parse a filter string for its operator and values.
     *
     * @param string $filter
     *   The filter string to parse.
     *
     * @return array|string[]
     *   The operator and value(s) to use for filtering.
     */
    protected static function parseOperator(string $filter): array
    {
        if (self::isSimpleFilter($filter)) {
            $filter = sprintf(
                '%s%s%s',
                config('laravel-request-scope.defaultOperator', 'eq'),
                config('laravel-request-scope.operatorSeparator', '|'),
                $filter
            );
        }

        [$operator, $value] = explode(config('laravel-request-scope.operatorSeparator', '|'), $filter, 2);

        /**
         * IMPORTANT! Operators here are not meant to be the same as Eloquent or
         * Query Builder methods. These are generic names (or symbols) that are
         * used outside of the RequestScope scope itself. Changing 'operator'
         * values here will break other applications and libraries that rely on
         * this parser class.
         *
         * Mapping to Query Builder or Eloquent methods/scopes happens inside of
         * the RequestScope class itself.
         */
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
                    'value' => explode(config('laravel-request-scope.betweenSeparator', ';'), $value, 2),
                ];
            case config('laravel-request-scope.likeOperator', 'like'):
                return [
                    'operator' => 'like',
                    'value' => '%' . self::escapeLike($value) . '%',
                ];
            case config('laravel-request-scope.startsWithOperator', 'sw'):
                return [
                    'operator' => 'like',
                    'value' => self::escapeLike($value) . '%',
                ];
            case config('laravel-request-scope.endsWithOperator', 'ew'):
                return [
                    'operator' => 'like',
                    'value' => '%' . self::escapeLike($value),
                ];
            case config('laravel-request-scope.equalOperator', 'eq'):
                return [
                    'operator' => '=',
                    'value' => $value,
                ];
            case config('laravel-request-scope.inOperator', 'in'):
                return [
                    'operator' => 'in',
                    'value' => explode(config('laravel-request-scope.inSeparator', ';'), $value),
                ];
            case config('laravel-request-scope.notInOperator', 'notin'):
                return [
                    'operator' => 'notIn',
                    'value' => explode(config('laravel-request-scope.inSeparator', ';'), $value),
                ];
        }

        throw new RuntimeException('Unsupported filter operator.');
    }

    /**
     * Determins how to handle null values based on the request filters and values
     *
     * @param string $operator
     *   Logical SQL operator after parsing
     * @param string $value
     *   Value sent in the request, after the operator (e.g. ne|snow)
     *
     * @return string
     *   directive how to handle received value
     *   possible outputs :
     *     'exclude nulls',
     *     'include nulls'
     *     'return nulls',
     *     'process value',
     */
    public static function handleNullValues(string $operator, string $value): string
    {
        $value = strtolower($value);
        if (in_array($operator, [ '<>', '!=' ])) {
            if ($value === 'null') {
                // ne|null
                return 'exclude nulls';
            } else {
                // ne|any_value
                return 'include nulls';
            }
        }

        if ($operator == '=') {
            if ($value === 'null') {
                // eq|null
                return 'return nulls';
            } else {
                // eq|any_value
                return 'process value';
            }
        }
    }
}
