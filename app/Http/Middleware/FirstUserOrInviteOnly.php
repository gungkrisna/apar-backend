<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirstUserOrInviteOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (User::all()->count() === 0) {
            return $next($request);
        } else if ($request->invite_token) {
            $inviteData = Invitation::getInviteData($request->invite_token);

            if ($inviteData) {
                $inviteEmail = $inviteData['email'];

                if ($inviteEmail !== $request->email) {
                    return response()->json([
                        'errors' => [
                            'email' => ['The invite code is not valid for this email address.'],
                        ],
                    ], 422);
                }

                $request->merge([
                    'role' => $inviteData['role'],
                ]);
                return $next($request);
            }
        } else {
            return abort(403, 'Forbidden');
        }
    }
}
