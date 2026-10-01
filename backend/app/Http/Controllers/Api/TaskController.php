<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     *
     * Supports query parameters:
     *   - status        : filter by TaskStatus value
     *   - assigned_to   : filter by user id (admin only; ignored for regular users)
     *   - search        : substring search on title (LIKE %search%)
     *   - per_page      : items per page, capped at 100, default 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        /** @var User $user */
        $user = $request->user();
        $perPage = min((int) ($request->query('per_page', 15)), 100);

        $tasks = Task::with('assignee')
            // Non-admins are always scoped to their own tasks regardless of params.
            ->when(! $user->isAdmin(), fn ($q) => $q->where('assigned_to', $user->id))
            // Admin may optionally filter by assignee.
            ->when($user->isAdmin() && $request->filled('assigned_to'),
                fn ($q) => $q->where('assigned_to', (int) $request->query('assigned_to'))
            )
            // Both roles may filter by status.
            ->when($request->filled('status'),
                fn ($q) => $q->where('status', $request->query('status'))
            )
            // Full-text-style LIKE search on title.
            ->when($request->filled('search'),
                fn ($q) => $q->where('title', 'like', '%'.$request->query('search').'%')
            )
            ->latest()
            ->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * POST /api/tasks → 201
     *
     * For non-admin users, assigned_to is silently forced to their own id
     * even if they submit a different value (the FormRequest strips the field
     * from the validated data, and here we force-set it).
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        if (! $user->isAdmin()) {
            $data['assigned_to'] = $user->id;
        }

        $task = Task::create($data);
        $task->refresh()->load('assignee');

        return response()->json(new TaskResource($task), 201);
    }

    /**
     * GET /api/tasks/{task} → 200
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load('assignee');

        return response()->json(new TaskResource($task));
    }

    /**
     * PUT /api/tasks/{task} → 200
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->refresh()->load('assignee');

        return response()->json(new TaskResource($task));
    }

    /**
     * DELETE /api/tasks/{task} → 204
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(null, 204);
    }
}
