<?php

namespace Database\Factories;

use App\Models\InboxItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Extraction>
 */
class ExtractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inbox_item_id' => InboxItem::factory(),
            'model_version' => 'claude-3-5-sonnet-20241022',
            'prompt_version' => 'v1.0.0',
            'raw_response' => [
                'suggestions' => [
                    [
                        'type' => 'event',
                        'title' => fake()->sentence(3),
                        'start_at' => fake()->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i:s'),
                    ],
                ],
            ],
        ];
    }
}
