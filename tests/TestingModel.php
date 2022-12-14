<?php

declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Submtd\LaravelRequestScope\Tests\Database\Factories\TestingModelFactory;

/**
 * Class TestingModel
 */
class TestingModel extends Model
{
    use HasFactory;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'testing_field_one',
        'testing_field_two',
        'testing_field_int',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'testing_field_one' => 'string',
        'testing_field_two' => 'string',
        'testing_field_int' => 'int',
    ];

    /**
     * {@inheritdoc}
     */
    protected static function newFactory(): TestingModelFactory
    {
        return TestingModelFactory::new();
    }
}
