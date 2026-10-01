<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /** Any authenticated user may list tasks (controller scopes the query). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Admins see all; users only see their own tasks. */
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }

    /** Any authenticated user may create a task. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Admins update any task; users only their own. */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }

    /** Admins delete any task; users only their own. */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->assigned_to === $user->id;
    }
}
