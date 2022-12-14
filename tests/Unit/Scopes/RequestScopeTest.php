<?php

declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests\Unit\Scopes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Submtd\LaravelRequestScope\Scopes\RequestScope;
use Submtd\LaravelRequestScope\Tests\TestCase;
use Submtd\LaravelRequestScope\Tests\TestingModel;

/**
 * Class RequestScopeTest
 *
 * @coversDefaultClass \Submtd\LaravelRequestScope\Scopes\RequestScope
 */
class RequestScopeTest extends TestCase
{
    protected ?Collection $foundModels = null;

    protected ?Collection $notFoundModels = null;

    public function dataProviderEloquentMappingData(): array
    {
        return [
            'can filter between values' => [
                [
                    'testing_field_int' => 'bt|2;6',
                ],
            ],
            'can filter in values' => [
                [
                    'testing_field_int' => 'in|3;5',
                ],
            ],
            'can filter not in values' => [
                [
                    'testing_field_int' => 'notin|-1;1;17',
                ],
            ],
        ];
    }

    public function dataProviderRequestTests(): array
    {
        return [
            'can filter by multiple and complex' => [
                [
                    'testing_field_one' => 'foo',
                    'testing_field_two' => 'foo,bar',
                ],
            ],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $findModelProps = [
            [
                'testing_field_one' => 'foo',
                'testing_field_two' => 'bar',
                'testing_field_int' => 3,
            ],
            [
                'testing_field_one' => 'foo',
                'testing_field_two' => 'foo',
                'testing_field_int' => 5,
            ],
        ];

        $notFindModelProps = [
            [
                'testing_field_one' => 'foo',
                'testing_field_int' => 1,
            ],
            [
                'testing_field_two' => 'foo',
                'testing_field_int' => 17,
            ],
            [
                'testing_field_two' => 'bar',
                'testing_field_int' => 17,
            ],
            [
                'testing_field_one' => 'bar',
                'testing_field_int' => 17,
            ],
            [
                'testing_field_two' => 'fizz',
                'testing_field_int' => 17,
            ],
            [
                'testing_field_one' => 'foo',
                'testing_field_two' => 'fizz',
                'testing_field_int' => 17,
            ],
            [
                'testing_field_one' => 'bar',
                'testing_field_two' => 'foo',
                'testing_field_int' => -1,
            ],
        ];

        $this->foundModels = TestingModel::factory()->createMany($findModelProps)->flatten();
        $this->notFoundModels = TestingModel::factory()->createMany($notFindModelProps)->flatten();
    }

    /**
     * @param array $filters
     *
     * @return void
     *
     * @dataProvider dataProviderRequestTests
     * @dataProvider dataProviderEloquentMappingData
     * @covers ::parseFilters
     */
    public function test_filters_map_to_eloquent_correctly(array $filters): void
    {
        Request::replace(['filter' => $filters]);

        $query = (new TestingModel())->newModelQuery();

        (new RequestScope())->apply($query, $query->getModel());

        $results = $query->get();

        self::assertCount($this->foundModels->count(), $results);
        self::assertEquals(
            $this->foundModels->pluck('id')->sort()->toArray(),
            $results->pluck('id')->sort()->toArray()
        );
    }
}
