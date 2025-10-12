<?php

namespace App\Traits;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HandlesBranchAccess
{
    protected function authorizeBranchAccess(bool $allowBranchUsers = true): User
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, __('message.permission_denied_for_account'));
        }

        if ($user->user_type === 'admin') {
            return $user;
        }

        if (! $allowBranchUsers) {
            abort(403, __('message.permission_denied_for_account'));
        }

        $user->loadMissing('branches');

        if ($user->can_access_all_branches || $user->branches->isNotEmpty()) {
            return $user;
        }

        abort(403, __('message.permission_denied_for_account'));
    }

    protected function getAccessibleBranchIds(User $user): ?array
    {
        if ($user->hasAccessToAllBranches()) {
            return null;
        }

        $user->loadMissing('branches');

        return $user->branches
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function applyBranchConstraint(Builder $query, ?array $branchIds, string $column = 'branch_id'): Builder
    {
        if ($branchIds !== null) {
            $query->whereIn($column, $branchIds);
        }

        return $query;
    }

    protected function ensureSpecialistAccessible(Specialist $specialist, ?array $branchIds): void
    {
        if ($branchIds === null) {
            return;
        }

        $specialist->loadMissing('branches');

        $specialistBranchIds = collect([$specialist->branch_id])
            ->merge($specialist->branches->pluck('id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($specialistBranchIds->intersect($branchIds)->isEmpty()) {
            abort(403, __('message.permission_denied_for_account'));
        }
    }

    protected function assertBranchAccessible(?int $branchId, ?array $branchIds): void
    {
        if ($branchIds === null) {
            return;
        }

        if (! $branchId || ! in_array((int) $branchId, $branchIds, true)) {
            abort(403, __('message.permission_denied_for_account'));
        }
    }
}
