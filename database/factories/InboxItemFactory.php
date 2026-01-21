<?php

namespace Database\Factories;

use App\Enums\InboxItemSource;
use App\Enums\InboxItemStatus;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InboxItem>
 */
class InboxItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'source' => fake()->randomElement(InboxItemSource::cases()),
            'raw_subject' => fake()->sentence(),
            'raw_content' => fake()->paragraphs(3, true),
            'received_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'status' => InboxItemStatus::New,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => InboxItemSource::Manual,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => InboxItemSource::Email,
        ]);
    }

    public function parsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InboxItemStatus::Parsed,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InboxItemStatus::Archived,
        ]);
    }
}
