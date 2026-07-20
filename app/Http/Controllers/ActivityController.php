<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Display the activity & notifications page.
     */
    public function index(Request $request): Response
    {
        $tab = $request->get('tab', 'notifications');
        $module = $request->get('module');
        $from = $request->get('from');
        $to = $request->get('to');

        // Notifications (from Laravel's database notifications channel)
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20, ['*'], 'notifications_page');

        // Activity Logs
        $activityQuery = ActivityLog::latest();

        if ($module && $module !== 'all') {
            $activityQuery->forModule($module);
        }

        if ($from || $to) {
            $activityQuery->forDateRange($from, $to);
        }

        $activityLogs = $activityQuery->paginate(15, ['*'], 'activity_page');

        $availableModules = collect(ActivityLog::moduleLabels())->unique()->values()->prepend('all')->toArray();

        return Inertia::render('Activity/Index', [
            'notifications' => $notifications,
            'activityLogs' => $activityLogs,
            'availableModules' => $availableModules,
            'filters' => [
                'tab' => $tab,
                'module' => $module ?? 'all',
                'from' => $from,
                'to' => $to,
            ],
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * API endpoint for polling unread notifications (used by NotificationBell).
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        $latest = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'body' => $n->data['body'] ?? '',
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $latest,
        ]);
    }
}
