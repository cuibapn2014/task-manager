<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate handled in controller via policy
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];

        // Only admins may reassign a task.
        if ($user->isAdmin()) {
            $rules['assigned_to'] = ['sometimes', 'integer', 'exists:users,id'];
        }

        return $rules;
    }
}
