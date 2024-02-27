<?php

namespace App\Http\Middleware;

use App\Helpers\V1\ResponseFormatter;
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
        if (User::count() === 0) {
            return $next($request);
        } else if ($inviteToken = $request->invite_token) {
            $inviteData = Invitation::getInviteData($inviteToken);

            if ($inviteData) {
                $inviteEmail = $inviteData['email'];

                if ($inviteEmail !== $request->email) {
                    return ResponseFormatter::error(422, 'Unprocessable Entity', [
                        'email' => ["Kode undangan tidak valid untuk alamat email {$request->email}."],
                    ]);
                }

                $request->merge([
                    'role' => $inviteData['role'],
                ]);

                return $next($request);
            }
            return ResponseFormatter::error(403, 'Forbidden');
        } else {
            return ResponseFormatter::error(403, 'Forbidden');
        }
    }
}
