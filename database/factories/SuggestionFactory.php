<?php

namespace Database\Factories;

use App\Enums\SuggestionStatus;
use App\Enums\SuggestionType;
use App\Models\Extraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Suggestion>
 */
class SuggestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(SuggestionType::cases());

        return [
            'extraction_id' => Extraction::factory(),
            'type' => $type,
            'payload' => $this->generatePayloadForType($type),
            'status' => SuggestionStatus::Proposed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generatePayloadForType(SuggestionType $type): array
    {
        return match ($type) {
            SuggestionType::Event => [
                'title' => fake()->sentence(3),
                'start_at' => fake()->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i:s'),
                'end_at' => fake()->dateTimeBetween('+1 week', '+2 weeks')->format('Y-m-d H:i:s'),
                'location' => fake()->optional()->address(),
            ],
            SuggestionType::Reminder => [
                'title' => fake()->sentence(3),
                'remind_at' => fake()->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i:s'),
            ],
            SuggestionType::Task => [
                'title' => fake()->sentence(3),
                'due_at' => fake()->optional()->dateTimeBetween('+1 day', '+1 week')?->format('Y-m-d H:i:s'),
                'priority' => fake()->randomElement(['low', 'medium', 'high']),
            ],
        };
    }

    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SuggestionType::Event,
            'payload' => $this->generatePayloadForType(SuggestionType::Event),
        ]);
    }

    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SuggestionType::Reminder,
            'payload' => $this->generatePayloadForType(SuggestionType::Reminder),
        ]);
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SuggestionType::Task,
            'payload' => $this->generatePayloadForType(SuggestionType::Task),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SuggestionStatus::Accepted,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SuggestionStatus::Rejected,
        ]);
    }
}
