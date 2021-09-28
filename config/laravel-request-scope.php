<?php

return [
    /**
     * query parameter to use for filtering results (e.g. ?filter[field]=)
     */
    'filterParameter' => 'filter',

    /**
     * filter separator. separates multiple filters. (e.g. ?filter[field]=eq|10,eq|5)
     */
    'filterSeparator' => ',',

    /**
     * operator separator. separates the operator from the filter (e.g. ?filter[field]=lt|10)
     */
    'operatorSeparator' => '|',

    /**
     * less than operator (e.g. ?filter[field]=lt|10)
     */
    'lessThanOperator' => 'lt',

    /**
     * less than or equal operator (e.g. ?filter[field]=lte|10)
     */
    'lessThanOrEqualOperator' => 'lte',

    /**
     * greater than operator (e.g. ?filter[field]=gt|10)
     */
    'greaterThanOperator' => 'gt',

    /**
     * greater than or equal operator (e.g. ?filter[field]=gte|10)
     */
    'greaterThanOrEqualOperator' => 'gte',

    /**
     * not equal operator (e.g. ?filter[field]=ne|10)
     */
    'notEqualOperator' => 'ne',

    /**
     * between operator (e.g. ?filter[field]=bt|1;10)
     */
    'betweenOperator' => 'bt',

    /**
     * between separator (e.g. ?filter[field]=bt|1;10)
     */
    'betweenSeparator' => ';',

    /**
     * in separator (e.g. ?filter[field]=in|1;10)
     */
    'inSeparator' => ';',

    /**
     * like operator (e.g. ?filter[field]=like|value)
     */
    'likeOperator' => 'like',

    /**
     * starts with operator (e.g. ?filter[field]=sw|value)
     */
    'startsWithOperator' => 'sw',

    /**
     * ends with operator (e.g. ?filter[field]=ew|value)
     */
    'endsWithOperator' => 'ew',

    /**
     * equal operator (e.g. ?filter[field]=eq|value)
     */
    'equalOperator' => 'eq',

    /**
     * in operator (e.g. ?filter[field]=in|value;value2)
     */
    'inOperator' => 'in',

    /**
     * not in operator (e.g. ?filter[field]=notin|value;value2)
     */
    'notInOperator' => 'notin',

    /**
     * default operator. This is the operator applied if none is set in the query parameter
     */
    'defaultOperator' => 'eq',

    /**
     * include parameter. This will eager load relationships
     */
    'includeParameter' => 'include',

    /**
     * sort parameter. This will sort the results
     */
    'sortParameter' => 'sort',

    /**
     * fields parameter. This will limit the returned fields in the query
     */
    'fieldsParameter' => 'fields',
];
