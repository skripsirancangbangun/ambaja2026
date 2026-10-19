<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $roles = $this->availableRolesFor($actor);

        $query = User::query()->orderBy('name');

        // Admins may manage staff accounts but cannot inspect Owner accounts.
        if ($actor->role !== 'Owner') {
            $query->where('role', '!=', 'Owner');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->input('role'), $roles, true)) {
            $query->where('role', $request->input('role'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users', 'roles'));
    }

    public function create(Request $request): View
    {
        return view('users.form', [
            'roles' => $this->availableRolesFor($request->user()),
            'pageTitle' => 'Tambah Pengguna',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $roles = $this->availableRolesFor($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($roles)],
            'is_active' => ['required', 'boolean'],
        ]);

        // Re-check the bootstrap condition after validation to prevent a forged
        // request from assigning Owner once an active Owner already exists.
        if (
            $data['role'] === 'Owner'
            && $request->user()->role !== 'Owner'
            && User::query()->where('role', 'Owner')->where('is_active', true)->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Role Owner hanya dapat ditetapkan oleh Owner setelah akun Owner aktif tersedia.']);
        }

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna baru berhasil dibuat.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureTargetIsManageable($request, $user);

        return view('users.form', [
            'user' => $user,
            'roles' => $this->availableRolesFor($request->user()),
            'pageTitle' => 'Edit Pengguna',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureTargetIsManageable($request, $user);

        $roles = $this->availableRolesFor($actor);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($roles)],
            'is_active' => ['required', 'boolean'],
        ]);

        $requestedActive = (bool) $data['is_active'];

        if ((int) $actor->id === (int) $user->id && ($data['role'] !== $user->role || !$requestedActive)) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Anda tidak dapat mengubah role atau menonaktifkan akun sendiri.']);
        }

        if ($this->wouldRemoveLastActiveOwner($user, $data['role'], $requestedActive)) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Minimal harus ada satu akun Owner yang aktif.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return redirect()
            ->route('users.index')
            ->with('status', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * This endpoint deactivates an account rather than deleting it, preserving
     * creator references and the audit trail of accounting transactions.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureTargetIsManageable($request, $user);

        if ((int) $actor->id === (int) $user->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if ($this->wouldRemoveLastActiveOwner($user, $user->role, false)) {
            return back()->withErrors(['user' => 'Minimal harus ada satu akun Owner yang aktif.']);
        }

        $user->forceFill(['is_active' => false])->save();

        return redirect()
            ->route('users.index')
            ->with('status', 'Akun pengguna dinonaktifkan. Riwayat transaksi tetap dipertahankan.');
    }

    /** @return list<string> */
    private function availableRolesFor(User $actor): array
    {
        $allowOwnerBootstrap = $actor->role === 'Admin'
            && !User::query()->where('role', 'Owner')->where('is_active', true)->exists();

        return RolePermissions::rolesAvailableTo($actor->role, $allowOwnerBootstrap);
    }

    private function ensureTargetIsManageable(Request $request, User $target): void
    {
        if ($request->user()->role !== 'Owner' && $target->role === 'Owner') {
            abort(403, 'Admin tidak dapat mengubah akun Owner.');
        }
    }

    private function wouldRemoveLastActiveOwner(User $target, string $newRole, bool $newActive): bool
    {
        if ($target->role !== 'Owner' || !$target->is_active) {
            return false;
        }

        if ($newRole === 'Owner' && $newActive) {
            return false;
        }

        return !User::query()
            ->where('role', 'Owner')
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->exists();
    }
}
