<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        // Search by name, email, or employee_id
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->integer('per_page', 15));

        return UserResource::collection($users)->response();
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return (new UserResource($user))->response();
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return (new UserResource($user))->response();
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
