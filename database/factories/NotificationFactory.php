<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['event_reminder', 'event_cancelled', 'event_updated', 'new_event', 'feedback_request'];
        $type = $this->faker->randomElement($types);
        
        $titles = [
            'event_reminder' => 'Event Reminder',
            'event_cancelled' => 'Event Cancelled',
            'event_updated' => 'Event Updated',
            'new_event' => 'New Event Available',
            'feedback_request' => 'Please Leave Feedback',
        ];

        $messages = [
            'event_reminder' => 'Don\'t forget! Your event is starting soon.',
            'event_cancelled' => 'Unfortunately, the event has been cancelled.',
            'event_updated' => 'The event details have been updated.',
            'new_event' => 'A new event matching your interests is now available.',
            'feedback_request' => 'Please share your feedback about the recent event.',
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'event_id' => \App\Models\Event::factory(),
            'type' => $type,
            'title' => $titles[$type],
            'message' => $messages[$type],
            'is_read' => $this->faker->boolean,
            'data' => json_encode(['event_id' => 1]), // Placeholder, should be updated when creating
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }
}
