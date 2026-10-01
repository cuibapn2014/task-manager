<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for /api/tasks CRUD, filters, and authorization boundaries.
 *
 * Response shape note:
 *   - Collection endpoints (index): {"data": [...], "meta": {...}, "links": {...}}
 *   - Single resource endpoints (show/store/update): flat object (no "data" wrapper)
 *     because the controller uses response()->json(new TaskResource($task)).
 */
class TaskTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAdmin(): array
    {
        $admin = User::factory()->admin()->create();

        return [$admin, $admin->createToken('t')->plainTextToken];
    }

    private function makeUser(): array
    {
        $user = User::factory()->user()->create();

        return [$user, $user->createToken('t')->plainTextToken];
    }

    // ── Index (collection) ───────────────────────────────────────────────────

    public function test_user_sees_only_own_tasks(): void
    {
        [$user, $token] = $this->makeUser();
        $other = User::factory()->create();

        Task::factory()->assignedTo($user)->count(3)->create();
        Task::factory()->assignedTo($other)->count(5)->create();

        $this->withToken($token)
            ->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_sees_all_tasks(): void
    {
        [, $token] = $this->makeAdmin();

        Task::factory()->count(7)->create();

        $this->withToken($token)
            ->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(7, 'data');
    }

    public function test_user_cannot_bypass_scope_via_assigned_to_param(): void
    {
        [$user, $token] = $this->makeUser();
        $other = User::factory()->create();
        Task::factory()->assignedTo($other)->count(4)->create();

        $this->withToken($token)
            ->getJson('/api/tasks?assigned_to='.$other->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_status_filter_returns_only_matching_tasks(): void
    {
        [$user, $token] = $this->makeUser();

        Task::factory()->assignedTo($user)->todo()->count(2)->create();
        Task::factory()->assignedTo($user)->done()->count(3)->create();

        $this->withToken($token)
            ->getJson('/api/tasks?status=done')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_search_filter_returns_matching_titles(): void
    {
        [$user, $token] = $this->makeUser();

        Task::factory()->assignedTo($user)->create(['title' => 'Fix the login bug']);
        Task::factory()->assignedTo($user)->create(['title' => 'Write documentation']);
        Task::factory()->assignedTo($user)->create(['title' => 'Fix navbar alignment']);

        $this->withToken($token)
            ->getJson('/api/tasks?search=Fix')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_is_paginated(): void
    {
        [$user, $token] = $this->makeUser();

        Task::factory()->assignedTo($user)->count(20)->create();

        $this->withToken($token)
            ->getJson('/api/tasks?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20);
    }

    public function test_per_page_is_capped_at_100(): void
    {
        [$user, $token] = $this->makeUser();

        Task::factory()->assignedTo($user)->count(150)->create();

        $this->withToken($token)
            ->getJson('/api/tasks?per_page=9999')
            ->assertOk()
            ->assertJsonCount(100, 'data');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    // ── Show (single resource — no "data" wrapper) ────────────────────────────

    public function test_user_can_view_own_task(): void
    {
        [$user, $token] = $this->makeUser();
        $task = Task::factory()->assignedTo($user)->create();

        $this->withToken($token)
            ->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('id', $task->id);
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        [, $token] = $this->makeUser();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($other)->create();

        $this->withToken($token)
            ->getJson("/api/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_show_missing_task_returns_404(): void
    {
        [, $token] = $this->makeUser();

        $this->withToken($token)
            ->getJson('/api/tasks/99999')
            ->assertNotFound();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_user_can_create_task_assigned_to_self(): void
    {
        [$user, $token] = $this->makeUser();

        $this->withToken($token)
            ->postJson('/api/tasks', ['title' => 'My Task'])
            ->assertStatus(201)
            ->assertJsonPath('assigned_to', $user->id);
    }

    public function test_user_cannot_create_task_assigned_to_someone_else(): void
    {
        [$user, $token] = $this->makeUser();
        $other = User::factory()->create();

        // submitted assigned_to is silently overridden to the auth user's id
        $this->withToken($token)
            ->postJson('/api/tasks', [
                'title' => 'Sneaky Task',
                'assigned_to' => $other->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('assigned_to', $user->id); // forced to self
    }

    public function test_store_validates_required_title(): void
    {
        [, $token] = $this->makeUser();

        $this->withToken($token)
            ->postJson('/api/tasks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_validates_status_enum(): void
    {
        [, $token] = $this->makeUser();

        $this->withToken($token)
            ->postJson('/api/tasks', ['title' => 'T', 'status' => 'invalid_status'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_create_task_assigned_to_any_user(): void
    {
        [, $adminToken] = $this->makeAdmin();
        $user = User::factory()->create();

        $this->withToken($adminToken)
            ->postJson('/api/tasks', [
                'title' => 'Admin Task',
                'assigned_to' => $user->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('assigned_to', $user->id);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_user_can_update_own_task(): void
    {
        [$user, $token] = $this->makeUser();
        $task = Task::factory()->assignedTo($user)->todo()->create();

        $this->withToken($token)
            ->putJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('status', 'done');
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        [, $token] = $this->makeUser();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($other)->create();

        $this->withToken($token)
            ->putJson("/api/tasks/{$task->id}", ['title' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_user_cannot_reassign_task_to_someone_else(): void
    {
        [$user, $token] = $this->makeUser();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create();

        // UpdateTaskRequest strips assigned_to for non-admins → field is ignored
        $this->withToken($token)
            ->putJson("/api/tasks/{$task->id}", ['assigned_to' => $other->id])
            ->assertOk()
            ->assertJsonPath('assigned_to', $user->id); // unchanged
    }

    public function test_admin_can_reassign_task(): void
    {
        [, $adminToken] = $this->makeAdmin();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create();

        $this->withToken($adminToken)
            ->putJson("/api/tasks/{$task->id}", ['assigned_to' => $other->id])
            ->assertOk()
            ->assertJsonPath('assigned_to', $other->id);
    }

    public function test_admin_can_update_any_task(): void
    {
        [, $adminToken] = $this->makeAdmin();
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create(['title' => 'Old Title']);

        $this->withToken($adminToken)
            ->putJson("/api/tasks/{$task->id}", ['title' => 'New Title'])
            ->assertOk()
            ->assertJsonPath('title', 'New Title');
    }

    public function test_update_validates_status_enum(): void
    {
        [$user, $token] = $this->makeUser();
        $task = Task::factory()->assignedTo($user)->create();

        $this->withToken($token)
            ->putJson("/api/tasks/{$task->id}", ['status' => 'not_valid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_user_can_delete_own_task(): void
    {
        [$user, $token] = $this->makeUser();
        $task = Task::factory()->assignedTo($user)->create();

        $this->withToken($token)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        [, $token] = $this->makeUser();
        $other = User::factory()->create();
        $task = Task::factory()->assignedTo($other)->create();

        $this->withToken($token)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_any_task(): void
    {
        [, $adminToken] = $this->makeAdmin();
        $user = User::factory()->create();
        $task = Task::factory()->assignedTo($user)->create();

        $this->withToken($adminToken)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_delete_missing_task_returns_404(): void
    {
        [, $token] = $this->makeAdmin();

        $this->withToken($token)
            ->deleteJson('/api/tasks/99999')
            ->assertNotFound();
    }

    // ── Users endpoint ───────────────────────────────────────────────────────

    public function test_admin_gets_all_users(): void
    {
        [, $adminToken] = $this->makeAdmin();
        User::factory()->count(3)->create();

        $this->withToken($adminToken)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(4, 'data'); // 3 + admin
    }

    public function test_user_gets_only_self_from_users_endpoint(): void
    {
        [$user, $token] = $this->makeUser();
        User::factory()->count(5)->create();

        $this->withToken($token)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user->id);
    }
}
