<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateUserSegmentationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserSegmentationController extends Controller
{
    /**
     * Update user segmentation fields (bay'ah status and level).
     */
    public function update(AdminUpdateUserSegmentationRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->back()->with('success', 'User segmentation updated successfully.');
    }
}
