<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'to' => fake()->e164PhoneNumber(),
            'content' => fake()->sentence(),
            'status' => 'pending',
            'failure_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'failure_reason' => null]);
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent', 'failure_reason' => null]);
    }

    public function delivered(): static
    {
        return $this->state(['status' => 'delivered', 'failure_reason' => null]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'failure_reason' => fake()->randomElement([
                'Invalid phone number',
                'Device offline',
                'Network timeout',
                'Insufficient balance',
            ]),
        ]);
    }

    public function withoutDevice(): static
    {
        return $this->state(['device_id' => null]);
    }
}
