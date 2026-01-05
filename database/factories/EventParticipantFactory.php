<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'event_id' => \App\Models\Event::factory(),
            'status' => 'registered',
            'is_paid' => false,
            'amount_paid' => 0,
            'payment_reference' => null,
            'payment_status' => 'pending',
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    public function attended()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'attended',
                'attended_at' => $this->faker->dateTimeBetween('now', '+1 day'),
                'is_paid' => true,
                'payment_status' => 'paid',
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function paid(float $amount)
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'amount_paid' => $amount,
            'payment_status' => 'paid',
            'payment_reference' => 'PAY-' . strtoupper($this->faker->bothify('##??####')),
            'paid_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
