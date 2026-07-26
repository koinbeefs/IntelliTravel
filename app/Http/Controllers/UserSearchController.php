<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    /**
     * Search users by username.
     */
    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2|max:50']);

        $query = $request->query('query');
        
        $users = User::where('username', 'like', "%{$query}%")
            ->select('id', 'username as name', 'email', 'profile_pic as avatar')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}