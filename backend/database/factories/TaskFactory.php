<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(fake()->numberBetween(3, 8)),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases())->value,
            // assigned_to must be provided explicitly or via for() — no default
            // because we cannot guarantee a user exists at definition time.
            'assigned_to' => User::factory(),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('-7 days', '+30 days')?->format('Y-m-d'),
        ];
    }

    /** Force status to 'todo'. */
    public function todo(): static
    {
        return $this->state(['status' => TaskStatus::Todo]);
    }

    /** Force status to 'in_progress'. */
    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatus::InProgress]);
    }

    /** Force status to 'done'. */
    public function done(): static
    {
        return $this->state(['status' => TaskStatus::Done]);
    }

    /** Assign to a specific user. */
    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }
}
