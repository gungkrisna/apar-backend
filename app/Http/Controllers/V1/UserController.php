<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Rules\ValidRole;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access users')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $filter = $request->query('filter');

            $query = User::orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
            }


            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%');
                });
                $filteredRowCount = $query->count();
            }

            $query->with('roles.permissions');

            $data = $query->paginate(perPage: $pageSize ?? $query->count(), page: $pageIndex ?? 0);

            $responseData = [
                'totalRowCount' => User::count(),
                'filteredRowCount' => $filteredRowCount ?? 0,
                'pageCount' => $data->lastPage(),
                'rows' => UserResource::collection($data),
            ];

            return ResponseFormatter::success(200, 'OK', $responseData);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Bad Request', $e->getMessage());
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $this->authorize('updateRole', $user);

        try {
            $validated = $request->validate([
                'role' => ['required', new ValidRole]
            ]);

            $user->syncRoles($validated['role']);
            $user->save();

            return ResponseFormatter::success(200, 'Success', new UserResource($user));
        } catch (Exception $e) {
            return ResponseFormatter::error(400, 'Failed', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            foreach ($request->id as $id) {
                $user = User::query()->findOrFail($id);
                $this->authorize('deleteUser', $user);
            }

            User::whereIn('id', $request->id)->delete();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
