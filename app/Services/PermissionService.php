<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Check if user has a permission (by slug).
     * Supports wildcard: admin.users.* matches admin.users.index, admin.users.create, etc.
     */
    public function hasPermission(User $user, string $permissionSlug): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $userPermissions = $this->getUserPermissionSlugs($user);

        if (in_array('*', $userPermissions)) {
            return true;
        }

        if (in_array($permissionSlug, $userPermissions)) {
            return true;
        }

        $parts = explode('.', $permissionSlug);
        if (count($parts) >= 2) {
            $prefix = implode('.', array_slice($parts, 0, -1)) . '.*';
            if (in_array($prefix, $userPermissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permission slugs for a user (from their role).
     */
    public function getUserPermissionSlugs(User $user): array
    {
        $cacheKey = 'user_permissions_' . $user->id;

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $role = $user->roleRelation;
            if (!$role) {
                return [];
            }
            return $role->permissions->pluck('slug')->all();
        });
    }

    /**
     * Clear permission cache for a user (call when role/permissions change).
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget('user_permissions_' . $user->id);
    }

    /**
     * Clear permission cache for all users with a role (call when role permissions change).
     */
    public function clearRoleCache(int $roleId): void
    {
        $userIds = User::where('role_id', $roleId)->pluck('id');
        foreach ($userIds as $id) {
            Cache::forget('user_permissions_' . $id);
        }
    }
}
