<?php

declare(strict_types=1);

namespace Submtd\LaravelRequestScope\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Submtd\LaravelRequestScope\Tests\TestingModel;

/**
 * Class TestingModelFactory
 */
class TestingModelFactory extends Factory {

    /**
     * {@inheritdoc}
     */
    protected $model = TestingModel::class;

    /**
     * {@inheritdoc}
     */
    public function definition(): array {
        return [
            'testing_field_one' => $this->faker->realText(15),
            'testing_field_two' => $this->faker->realText(15),
        ];
    }

}

