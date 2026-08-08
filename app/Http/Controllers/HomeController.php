<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat;
use Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, $id = null)
    {
        $messages = [];
        $otherUser = null;
        $group_id = null;

        if ($id) {
            $otherUser = User::findOrFail($id);
            $group_id = (Auth::id() > $id) ? Auth::id().$id : $id.Auth::id();
            $messages = Chat::where('group_id', $group_id)
                ->orderBy('created_at')
                ->get()
                ->toArray();

            // Mark messages sent TO me in this conversation as read
            Chat::where('group_id', $group_id)
                ->where('other_user_id', Auth::id())
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        $friends = User::where('id', '!=', Auth::id())
            ->get()
            ->map(function ($friend) {
                $friend->unread_count = Chat::where('user_id', $friend->id)
                    ->where('other_user_id', Auth::id())
                    ->where('is_read', 0)
                    ->count();
                return $friend;
            })
            ->toArray();

        return view('home', compact('friends', 'messages', 'otherUser', 'id', 'group_id'));
    }
}