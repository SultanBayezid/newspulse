<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return UserResource::collection(
            User::query()
                ->with('avatar')
                ->withCount('articles')
                ->when(
                    $request->filled('role'),
                    fn ($q) => $q->where('role', $request->query('role'))
                )
                ->when(
                    $request->query('status') === 'banned',
                    fn ($q) => $q->whereNotNull('banned_at')
                )
                ->when(
                    $request->query('status') === 'active',
                    fn ($q) => $q->whereNull('banned_at')
                )
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where(
                        fn ($query) => $query
                            ->where('name', 'like', '%' . $request->query('q') . '%')
                            ->orWhere('email', 'like', '%' . $request->query('q') . '%')
                    )
                )
                ->latest()
                ->paginate(min(100, (int) $request->query('per_page', 25)))
                ->withQueryString()
        );
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);

        $user = User::create([
            ...$request->validated(),
            'email_verified_at' => now(),
        ]);

        $this->logger->log('user.created', 'Created user account for ' . $user->email, $user);

        return new UserResource($user->load('avatar'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        $this->guardLastSuperAdmin($request->user(), $user, $request->input('role'));

        $data = collect($request->validated());

        $user->fill($data->except('password')->all());

        if ($data->filled('password')) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->logger->log('user.updated', 'Updated user account for ' . $user->email, $user);

        return new UserResource($user->fresh('avatar')->loadCount('articles'));
    }

    public function destroy(Request $request, User $user): Response
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot delete your own account.');

        $this->authorize('delete', $user);

        $email = $user->email;
        $user->delete();

        $this->logger->log('user.deleted', 'Deleted user account for ' . $email);

        return response()->noContent();
    }

    public function ban(Request $request, User $user): UserResource
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot ban your own account.');

        $this->authorize('ban', $user);

        $user->forceFill(['banned_at' => now()])->save();
        $user->tokens()->delete();

        $this->logger->log('user.banned', 'Banned user ' . $user->email, $user);

        return new UserResource($user->fresh('avatar'));
    }

    public function unban(Request $request, User $user): UserResource
    {
        $this->authorize('ban', $user);

        $user->forceFill(['banned_at' => null])->save();

        $this->logger->log('user.unbanned', 'Restored user ' . $user->email, $user);

        return new UserResource($user->fresh('avatar'));
    }

    /**
     * Never allow the system to end up without a super admin.
     */
    private function guardLastSuperAdmin(User $actor, User $target, ?string $newRole): void
    {
        if ($target->role->value !== 'super_admin' || $newRole === 'super_admin') {
            return;
        }

        $otherSuperAdmins = User::query()
            ->where('role', 'super_admin')
            ->whereKeyNot($target->id)
            ->exists();

        if (! $otherSuperAdmins) {
            throw ValidationException::withMessages([
                'role' => 'You cannot demote the last remaining super admin.',
            ]);
        }
    }
}
