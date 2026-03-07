<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RbacModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PermissionsController extends Controller
{
    public function index()
    {
        $modules = $this->getModulesForInertia();

        return Inertia::render('Admin/Permissions/Index', [
            'modules' => $modules,
        ]);
    }

    public function storeModule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:rbac_modules,slug',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if (! str_starts_with($slug, 'admin.')) {
            $slug = 'admin.' . $slug;
        }

        RbacModule::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? RbacModule::max('sort_order') + 10,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Module created successfully.');
    }

    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:rbac_modules,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string|max:500',
        ]);

        $module = RbacModule::findOrFail($validated['module_id']);
        $slug = $validated['slug'] ?? $module->slug . '.' . Str::slug($validated['name']);

        Permission::create([
            'module_id' => $module->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function updateModule(Request $request, RbacModule $rbac_module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:rbac_modules,slug,' . $module->id,
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $rbac_module->update($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Module updated successfully.');
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $permission->id,
            'description' => 'nullable|string|max:500',
        ]);

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroyModule(RbacModule $rbac_module)
    {
        if ($rbac_module->permissions()->exists()) {
            return back()->with('error', 'Cannot delete module with permissions. Delete permissions first.');
        }
        $rbac_module->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Module deleted successfully.');
    }

    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    private function getModulesForInertia(): array
    {
        return RbacModule::with('permissions')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'slug' => $m->slug,
                'description' => $m->description,
                'sort_order' => $m->sort_order,
                'permissions' => $m->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'description' => $p->description,
                ])->values()->all(),
            ])->all();
    }
}
