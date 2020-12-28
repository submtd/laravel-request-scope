<?php
declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests\Unit\Services;

use RuntimeException;
use Submtd\LaravelRequestScope\Services\FilterParser;
use Submtd\LaravelRequestScope\Tests\TestCase;

/**
 * Class FilterParserTest
 *
 * @coversDefaultClass \Submtd\LaravelRequestScope\Services\FilterParser
 */
class FilterParserTest extends TestCase {

    public function dataProviderEscapeLike(): array {
        return [
            'unchanged if no wildcard' => ['like|example', 'like', '%example%'],
            'escaped if wildcard at front' => ['like|%example', 'like', '%\%example%'],
            'escaped if wildcard at end' => ['like|example%', 'like', '%example\%%'],
            'escaped if wildcard in middle' => ['like|exam%ple', 'like', '%exam\%ple%'],
            'escaped if wildcard in more than one place' => ['like|%exam%ple%', 'like', '%\%exam\%ple\%%'],
        ];
    }

    public function dataProviderIsSimpleFilter(): array {
        return [
            'string missing separator is simple' => ['example', '=', 'example'],
            'string with separator is not simple' => ['gt|example', '>', 'example'],
        ];
    }

    public function dataProviderMultipleSeparators(): array {
        return [
            'no separator works' => ['example', '=', 'example'],
            'one separator works' => ['eq|example', '=', 'example'],
            'multiple separators work' => ['eq|exam|ple', '=', 'exam|ple'],
        ];
    }

    public function dataProviderParseOperator(): array {
        return [
            'lt is supported and works' => ['lt|example', '<', 'example'],
            'lte is supported and works' => ['lte|example', '<=', 'example'],
            'gt is supported and works' => ['gt|example', '>', 'example'],
            'gte is supported and works' => ['gte|example', '>=', 'example'],
            'ne is supported and works' => ['ne|example', '<>', 'example'],
            'bt is supported and works' => ['bt|foo;bar', 'between', ['foo', 'bar']],
            'like is supported and works' => ['like|example', 'like', '%example%'],
            'sw is supported and works' => ['sw|example', 'like', 'example%'],
            'ew is supported and works' => ['ew|example', 'like', '%example'],
            'eq is supported and works' => ['eq|example', '=', 'example'],
        ];
    }

    /**
     * @covers ::parse
     * @covers ::isSimpleFilter
     * @covers ::escapeLike
     * @covers ::parseOperator
     *
     * @dataProvider dataProviderIsSimpleFilter
     * @dataProvider dataProviderEscapeLike
     * @dataProvider dataProviderParseOperator
     * @dataProvider dataProviderMultipleSeparators
     *
     * @param string $filter
     * @param string $expectedOperator
     * @param string|array $expectedValue
     */
    public function test_parse(string $filter, string $expectedOperator, $expectedValue): void {
        $result = FilterParser::parse(['foo' => $filter]);
        self::assertEquals($expectedOperator, $result['foo'][0]['operator']);
        self::assertEquals($expectedValue, $result['foo'][0]['value']);
    }

    /**
     * @covers ::parse
     * @depends test_parse
     */
    public function test_parse_splits_separated_filters(): void {
        $result = FilterParser::parse([
            'foo' => 'eq|3,eq|5',
            'bar' => 'lt|10',
        ]);

        self::assertCount(2, $result['foo']);
        self::assertEquals([
            ['operator' => '=', 'value' => '3'],
            ['operator' => '=', 'value' => '5'],
        ], $result['foo']);
        self::assertCount(1, $result['bar']);
        self::assertEquals(['operator' => '<', 'value' => '10'], $result['bar'][0]);
    }

    /**
     * @covers ::parseOperator
     * @depends test_parse
     */
    public function test_parse_throws_unsupported_exception(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported filter operator.');
        FilterParser::parse(['foo' => 'abc123|def456']);
    }

}
