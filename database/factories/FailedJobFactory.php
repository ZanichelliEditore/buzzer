<?php

namespace Database\Factories;

use App\Models\FailedJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FailedJob>
 */
class FailedJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'connection' => $this->faker->text(),
            'queue' => $this->faker->text(),
            'payload' => $this->faker->text(),
            'exception' => $this->faker->text(),
            'failed_at' => now(),
        ];
    }
}
