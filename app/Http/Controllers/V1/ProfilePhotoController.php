<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProfilePhotoRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProfilePhotoRequest $request)
    {
        $user = User::find(auth()->id());

        $photoPath = $request->file('photo')->store('photos', 'public');

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->photo = $photoPath;
        $user->save();

        return ResponseFormatter::success(data: [
            'photo_url' => Storage::url($user->photo),
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $user = User::find(auth()->id());

        if (!$user->photo) {
            return ResponseFormatter::error('404', 'Not Found');
        }

        Storage::delete($user->photo);

        $user->photo = null;
        $user->save();

        return ResponseFormatter::success();
    }
}
