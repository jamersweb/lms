<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RbacModule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Dashboard', 'slug' => 'admin.dashboard', 'description' => 'Admin dashboard', 'sort_order' => 0],
            ['name' => 'Users', 'slug' => 'admin.users', 'description' => 'User management', 'sort_order' => 10],
            ['name' => 'Courses', 'slug' => 'admin.courses', 'description' => 'Course management', 'sort_order' => 20],
            ['name' => 'Modules', 'slug' => 'admin.modules', 'description' => 'Course module management', 'sort_order' => 25],
            ['name' => 'Lessons', 'slug' => 'admin.lessons', 'description' => 'Lesson management', 'sort_order' => 30],
            ['name' => 'Habits', 'slug' => 'admin.habits', 'description' => 'Habit management', 'sort_order' => 40],
            ['name' => 'WhatsApp', 'slug' => 'admin.whatsapp-settings', 'description' => 'WhatsApp settings', 'sort_order' => 50],
            ['name' => 'Broadcasts', 'slug' => 'admin.broadcasts', 'description' => 'Broadcast management', 'sort_order' => 60],
            ['name' => 'Ask', 'slug' => 'admin.ask', 'description' => 'Ask portal (legacy)', 'sort_order' => 70],
            ['name' => 'Questions', 'slug' => 'admin.questions', 'description' => 'Student questions', 'sort_order' => 75],
            ['name' => 'Moderation', 'slug' => 'admin.moderation', 'description' => 'Content moderation', 'sort_order' => 80],
            ['name' => 'Analytics', 'slug' => 'admin.analytics', 'description' => 'Analytics and reports', 'sort_order' => 90],
            ['name' => 'Dua Wall', 'slug' => 'admin.dua-wall', 'description' => 'Dua wall moderation', 'sort_order' => 95],
            ['name' => 'Notifications', 'slug' => 'admin.notifications', 'description' => 'Notification settings', 'sort_order' => 100],
            ['name' => 'Micro Nudges', 'slug' => 'admin.micro-nudges', 'description' => 'Micro nudges and campaigns', 'sort_order' => 110],
            ['name' => 'Roles', 'slug' => 'admin.roles', 'description' => 'Role management', 'sort_order' => 120],
            ['name' => 'Permissions', 'slug' => 'admin.permissions', 'description' => 'Permission overview', 'sort_order' => 130],
        ];

        $createdModules = [];
        foreach ($modules as $m) {
            $mod = RbacModule::updateOrCreate(
                ['slug' => $m['slug']],
                $m
            );
            $createdModules[$m['slug']] = $mod;
        }

        $permissionActions = ['index', 'create', 'edit', 'delete', 'show'];
        $permissionsByModule = [];

        foreach ($createdModules as $slug => $module) {
            foreach ($permissionActions as $action) {
                $permSlug = $slug . '.' . $action;
                $perm = Permission::updateOrCreate(
                    ['slug' => $permSlug],
                    [
                        'module_id' => $module->id,
                        'name' => ucfirst($action),
                        'description' => ucfirst($action) . ' ' . $module->name,
                    ]
                );
                $permissionsByModule[$slug][] = $perm;
            }
        }

        // Create roles
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Full access to all admin features']
        );
        $adminRole->permissions()->sync(Permission::pluck('id'));

        $mentorRole = Role::updateOrCreate(
            ['slug' => 'mentor'],
            ['name' => 'Mentor', 'description' => 'Limited admin access for mentors']
        );
        $mentorPermIds = collect();
        // Mentor: dashboard index; whatsapp-settings all; ask all; questions all; users index+show; broadcasts all
        foreach (['admin.dashboard' => ['index'], 'admin.whatsapp-settings' => $permissionActions, 'admin.ask' => $permissionActions, 'admin.questions' => $permissionActions, 'admin.users' => ['index', 'show'], 'admin.broadcasts' => $permissionActions] as $modSlug => $actions) {
            if (isset($permissionsByModule[$modSlug])) {
                $mentorPermIds = $mentorPermIds->merge(
                    collect($permissionsByModule[$modSlug])->filter(fn ($p) => in_array(explode('.', $p->slug)[2] ?? '', $actions))->pluck('id')
                );
            }
        }
        $mentorRole->permissions()->sync($mentorPermIds->unique());

        $studentRole = Role::updateOrCreate(
            ['slug' => 'student'],
            ['name' => 'Student', 'description' => 'No admin access']
        );
        $studentRole->permissions()->sync([]);

        // Migrate existing users: role column -> role_id
        $roleMap = ['admin' => $adminRole->id, 'mentor' => $mentorRole->id, 'student' => $studentRole->id];
        foreach (User::whereNull('role_id')->get() as $user) {
            $slug = $user->getRawOriginal('role') ?? ($user->is_admin ? 'admin' : 'student');
            $roleId = $roleMap[$slug] ?? $studentRole->id;
            $user->update(['role_id' => $roleId]);
        }
    }
}
