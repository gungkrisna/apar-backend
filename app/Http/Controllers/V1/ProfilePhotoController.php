<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProfilePhotoRequest;
use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    /**
     * Store a newly created user profile photo in storage.
     */
    public function store(StoreProfilePhotoRequest $request)
    {
        $user = User::find(auth()->id());

        $path = $request->file('photo')->store('photos', 'public');

        if ($user->photo) {
            $user->photo->delete();
            Storage::disk('public')->delete($user->photo->path);
        }

        $image = Image::create(['path' => $path]);
        $image->collection_name = 'profile_photo';

        $user->photo()->save($image);

        return ResponseFormatter::success(data: [
            'photo_url' => Storage::url($path),
        ]);
    }
    /**
     * Remove the specified user profile photo from storage.
     */
    public function destroy()
    {
        $user = User::find(auth()->id());

        if (!$user->photo) {
            return ResponseFormatter::error('404', 'Not Found');
        }

        Storage::disk('public')->delete($user->photo->path);

        $user->photo->delete();

        return ResponseFormatter::success();
    }
}
