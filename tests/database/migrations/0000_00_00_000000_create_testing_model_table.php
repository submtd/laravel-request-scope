<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestingModelTable extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('testing_models');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(
            'testing_models',
            static function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('testing_field_one')->default('')->index();
                $table->string('testing_field_two')->default('')->index();
                $table->integer('testing_field_int')->default(0)->index();
            }
        );
    }
}
