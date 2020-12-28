<?php
declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests\Unit\Scopes;


use Illuminate\Support\Facades\Request;
use Submtd\LaravelRequestScope\Scopes\RequestScope;
use Submtd\LaravelRequestScope\Tests\TestCase;
use Submtd\LaravelRequestScope\Tests\TestingModel;

/**
 * Class RequestScopeTest
 *
 * @coversDefaultClass \Submtd\LaravelRequestScope\Scopes\RequestScope
 */
class RequestScopeTest extends TestCase {

    /**
     * @covers ::parseFilters
     */
    public function test_parseFilters_builds_complex_query(): void {

        Request::replace([
            'filter' => [
                'testing_field_one' => 'foo',
                'testing_field_two' => 'foo,bar',
            ],
        ]);

        $find_models =
            TestingModel::factory(1)->createMany([
                [
                    'testing_field_one' => 'foo',
                    'testing_field_two' => 'bar',
                ],
                [
                    'testing_field_one' => 'foo',
                    'testing_field_two' => 'foo',
                ],
            ])->flatten();

        $not_find_models =
            TestingModel::factory(1)->createMany([
                ['testing_field_one' => 'foo'],
                ['testing_field_two' => 'foo'],
                ['testing_field_two' => 'bar'],
                ['testing_field_one' => 'bar'],
                ['testing_field_two' => 'fizz'],
                [
                    'testing_field_one' => 'foo',
                    'testing_field_two' => 'fizz',
                ],
                [
                    'testing_field_one' => 'bar',
                    'testing_field_two' => 'foo',
                ],
            ])->flatten();


        /** @var TestingModel $model */
        $model = TestingModel::create();
        $query = $model->newModelQuery();

        $scope = new RequestScope();
        $scope->apply($query, $model);

        $results = $query->get();

        self::assertCount(2, $results);
        self::assertEquals($find_models->pluck('id')->sort()->toArray(),
            $results->pluck('id')->sort()->toArray());
    }

}
