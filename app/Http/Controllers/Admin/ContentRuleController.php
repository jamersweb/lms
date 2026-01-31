<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentRuleController extends Controller
{
    /**
     * Upsert (create or update) a content rule for an entity.
     */
    public function upsert(string $type, int $id, Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $entity = $this->resolveEntity($type, $id);

        $validated = $request->validate([
            'min_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'expert'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'requires_bayah' => ['boolean'],
        ]);

        // Convert empty strings to null for nullable fields
        $validated['min_level'] = $validated['min_level'] ?? null;
        $validated['gender'] = $validated['gender'] ?? null;
        $validated['requires_bayah'] = $validated['requires_bayah'] ?? false;

        $entity->contentRule()->updateOrCreate([], $validated);

        return redirect()->back()->with('success', 'Content rule saved successfully.');
    }

    /**
     * Delete a content rule for an entity.
     */
    public function destroy(string $type, int $id)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $entity = $this->resolveEntity($type, $id);

        if ($entity->contentRule) {
            $entity->contentRule->delete();
        }

        return redirect()->back()->with('success', 'Content rule removed successfully.');
    }

    /**
     * Resolve entity model by type and id.
     */
    private function resolveEntity(string $type, int $id)
    {
        return match ($type) {
            'courses' => Course::findOrFail($id),
            'modules' => Module::findOrFail($id),
            'lessons' => Lesson::findOrFail($id),
            default => abort(404, 'Invalid entity type.'),
        };
    }
}
