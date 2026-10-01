<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];

        // Only admins may set assigned_to; users are always assigned to themselves.
        if ($user->isAdmin()) {
            $rules['assigned_to'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }
}
