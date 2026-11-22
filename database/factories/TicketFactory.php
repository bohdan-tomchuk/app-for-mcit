<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::New,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TicketStatus::InProgress]);
    }

    public function done(): static
    {
        return $this->state(['status' => TicketStatus::Done]);
    }
}
