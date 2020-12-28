<?php
declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Submtd\LaravelRequestScope\Providers\LaravelRequestScopeServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase {

    use RefreshDatabase;

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app): array {
        return array_merge(
            parent::getPackageProviders($app),
            [
                LaravelRequestScopeServiceProvider::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        parent::setUp();
        // Uncommenting this will allow better test debugging at the expense of some
        // tests failing because they are supposed to throw exceptions that get
        // caught, and they no longer get caught.
        // $this->withoutExceptionHandling();

        $this->loadMigrationsFrom(realpath(__DIR__ . '/database/migrations'));

        Config::set('laravel-request-scope', [
            'filterParameter' => 'filter',
            'filterSeparator' => ',',
            'operatorSeparator' => '|',
            'lessThanOperator' => 'lt',
            'lessThanOrEqualOperator' => 'lte',
            'greaterThanOperator' => 'gt',
            'greaterThanOrEqualOperator' => 'gte',
            'notEqualOperator' => 'ne',
            'betweenOperator' => 'bt',
            'betweenSeparator' => ';',
            'likeOperator' => 'like',
            'startsWithOperator' => 'sw',
            'endsWithOperator' => 'ew',
            'equalOperator' => 'eq',
            'defaultOperator' => 'eq',
            'includeParameter' => 'include',
            'sortParameter' => 'sort',
            'fieldsParameter' => 'fields',
        ]);
    }

}
