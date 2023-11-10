<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param User $loggedInUser the user that's trying to update the role of $user
     * @param User $user the user whose role is being updated by the $loggedInUser
     * @return bool
     */
    public function updateRole(User $loggedInUser, User $user)
    {
        if ($loggedInUser->id === $user->id) {
            return false;
        }

        if ($loggedInUser->can("update roles")) {
            if ($user->hasRole("Super Admin")) {
                return User::getNumberOfAdmins() > 1;
            } else return true;
        } else return false;
    }

    /**
     * @param User $loggedInUser The user who is attempting to delete the user account.
     * @param User $user The user account to be deleted.
     * @return bool
     */
    public function deleteUser(User $loggedInUser, User $user)
    {
        if ($loggedInUser->id === $user->id) {
            return false;
        }

        if ($loggedInUser->can("delete users")) {
            if ($user->hasRole("Super Admin")) {
                return User::getNumberOfAdmins() > 1;
            } else return true;
        } else return false;
    }

    /**
     * Prevent the user from deleting their own account if they're the only Super Admin, and the other users still exist.
     * @param User $loggedInUser the user that's trying to delete their account
     */
    public function destroyProfile(User $loggedInUser): Response
    {
        if ($loggedInUser->hasRole('Super Admin')) {
            if (User::all()->count() > 1)
                if (User::getNumberOfAdmins() == 1) {
                    return Response::deny('You cannot delete your own account if you\'re the only Super Admin and other users still exist.');
                };
        }

        return Response::allow();
    }
}
