<?php

namespace App\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_id',
        'invite_token',
        'email',
        'role',
        'expired_at',
        'accepted',
    ];

    protected $dates = [
        'expired_at'
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'role' => 'string'
    ];

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public static function generateInvitationToken($email) {
        return substr(md5(rand(0, 9) . $email . time()), 0, 32);
    }

    public static function getInviteData($token) {
        $invitation = self::where('invite_token', $token)
            ->where('expired_at', '>', Carbon::now())
            ->where('accepted', false)
            ->first();

        if ($invitation) {
            return [
                'email' => $invitation->email,
                'role' => $invitation->role
            ];
        }

        return null;
    }
}
