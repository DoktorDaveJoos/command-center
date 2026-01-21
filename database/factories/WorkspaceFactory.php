<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company()."'s Workspace",
            'inbound_email_token' => Str::random(32),
        ];
    }

    public function withOwner(\App\Models\User $user): static
    {
        return $this->afterCreating(function (Workspace $workspace) use ($user) {
            $workspace->users()->attach($user->id, ['role' => 'owner']);
        });
    }
}
