<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * GET /api/users
     *
     * Admin  → all users (id, name, role) — used for the assignee dropdown.
     * User   → only themselves.
     *
     * Email is included in UserResource (it is not a secret for the user
     * viewing themselves, and admins need it for identification).
     * Password hashes are never exposed (hidden on the model).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $auth */
        $auth = $request->user();

        $users = $auth->isAdmin()
            ? User::orderBy('name')->get()
            : User::where('id', $auth->id)->get();

        return UserResource::collection($users);
    }
}
