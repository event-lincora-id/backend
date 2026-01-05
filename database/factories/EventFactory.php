<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+3 months');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 48) . ' hours');
        
        $isPaid = $this->faker->boolean(40);
        $price = $isPaid ? $this->faker->numberBetween(50000, 1000000) : 0;
        
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'location' => $this->faker->address,
            'meeting_link' => $this->faker->boolean(30) ? $this->faker->url : null,
            'event_type' => $this->faker->randomElement(['offline', 'online', 'hybrid']),
            'contact_info' => $this->faker->phoneNumber,
            'requirements' => $this->faker->paragraph,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_paid' => $isPaid,
            'price' => $price,
            'quota' => $this->faker->numberBetween(10, 500),
            'registered_count' => 0, // Will be updated by callbacks or observers if needed
            'status' => 'published',
            'is_active' => true,
            'user_id' => \App\Models\User::factory(), // Default, can be overridden
            'category_id' => \App\Models\Category::inRandomOrder()->first()?->id ?? 1,
            'image' => null, // Or a default placeholder path
        ];
    }

    public function published()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'is_active' => true,
        ]);
    }

    public function draft()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_active' => true,
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
            'end_date' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }
}
