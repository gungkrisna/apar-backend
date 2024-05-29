<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserInviteRequest;
use App\Models\Invitation;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;

class UserInviteController extends Controller
{
    public function store(StoreUserInviteRequest $request)
    {
        $validated = $request->validated();

        $email = $validated['email'];
        $role = $validated['role'];
        $inviteToken = Invitation::generateInvitationToken($email);

        $invitation = Invitation::create([
            'inviter_id' => $request->user()->id,
            'invite_token' => $inviteToken,
            'email' => $email,
            'role' => $role,
            'expired_at' => now()->addDay(),
        ]);

        try {
            Notification::route('mail', $email)->notify(new UserInvited($invitation, $request->user()));
        } catch (\Exception $e) {
            return ResponseFormatter::error(500, 'Failed to send invite', $e->getMessage());
        }
        return ResponseFormatter::success(data: $invitation);
    }

    public function show(string $inviteToken)
    {
        $data = Invitation::getInviteData($inviteToken);
        if ($data) {
            return ResponseFormatter::success(data: $data);
        } else {
            return ResponseFormatter::error(404, 'Not Found');
        }
    }
}
