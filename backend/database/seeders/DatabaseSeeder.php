<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Fixed admin ──────────────────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // ── Fixed regular users ──────────────────────────────────────────────
        $alice = User::factory()->user()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        $bob = User::factory()->user()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        // ── ~30 tasks spread across the three users ─────────────────────────
        // Roughly equal distribution: 10 each.
        foreach ([$admin, $alice, $bob] as $user) {
            Task::factory(10)->assignedTo($user)->create();
        }
    }
}
