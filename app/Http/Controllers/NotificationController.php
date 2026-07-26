<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // GET /notifications
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'timestamp' => $n->created_at->toISOString(),
                'read' => $n->read,
                'icon' => $n->icon,
                'actionLabel' => $n->action_label,
                'actionId' => $n->action_type,
                'data' => $n->data,
            ];
        }));
    }

    // PUT /notifications/{id}/read
    public function markRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    // PUT /notifications/read-all
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->update(['read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // DELETE /notifications/{id}
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    // POST /notifications/{id}/action
    public function handleAction(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Allow override of action_type from request body (for decline button)
        $actionType = $request->input('action_type', $notification->action_type);
        $data = $notification->data ?? [];

        // Handle different action types
        if ($actionType === 'accept_invite' && isset($data['trip_id'])) {
            $trip = \App\Models\Trip::findOrFail($data['trip_id']);
            
            // Update or add user to trip_user with accepted status
            DB::table('trip_user')
                ->where('trip_id', $data['trip_id'])
                ->where('user_id', $request->user()->id)
                ->update(['status' => 'accepted']);

            // Mark notification as read
            $notification->update(['read' => true]);

            return response()->json(['message' => 'Invitation accepted']);
        }

        if ($actionType === 'decline_invite' && isset($data['trip_id'])) {
            // Remove user from trip_user
            DB::table('trip_user')
                ->where('trip_id', $data['trip_id'])
                ->where('user_id', $request->user()->id)
                ->delete();

            // Mark notification as read and delete it
            $notification->update(['read' => true]);
            $notification->delete();

            return response()->json(['message' => 'Invitation declined']);
        }

        return response()->json(['message' => 'Action handled'], 200);
    }
}
