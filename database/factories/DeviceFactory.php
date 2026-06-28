<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fcm_token' => fake()->uuid(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'last_seen_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'last_seen_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactive',
            'last_seen_at' => fake()->dateTimeBetween('-30 days', '-2 days'),
        ]);
    }
}
