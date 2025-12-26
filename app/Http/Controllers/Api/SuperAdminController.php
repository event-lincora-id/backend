<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    /**
     * Get all users (participants, organizers, super admins)
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $role = $request->get('role'); // participant, admin, super_admin
        $status = $request->get('status'); // active, suspended

        // Build query for all users with relationships
        $query = User::with(['events', 'eventParticipants']);

        // Apply role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('full_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Apply status filter (suspended users have suspended_at not null)
        if ($status === 'suspended') {
            $query->whereNotNull('suspended_at');
        } elseif ($status === 'active') {
            $query->whereNull('suspended_at');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform data for response
        $transformedUsers = $users->getCollection()->map(function ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'status' => $user->suspended_at ? 'suspended' : 'active',
                'suspended_at' => $user->suspended_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            // Add event stats for organizers
            if ($user->role === 'admin') {
                $events = $user->events;
                $userData['events_count'] = $events->count();
                $userData['published_events_count'] = $events->where('status', 'published')->count();
            }

            // Add participation stats for participants
            if ($user->role === 'participant') {
                $participations = $user->eventParticipants;
                $userData['events_joined'] = $participations->count();
                $userData['events_attended'] = $participations->where('status', 'attended')->count();
            }

            return $userData;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $transformedUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'has_more_pages' => $users->hasMorePages(),
                ]
            ]
        ]);
    }

    /**
     * Get all event organizers (admins) with their events
     */
    public function getOrganizers(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $status = $request->get('status'); // active, inactive
        
        // Build query for organizers (users with admin role)
        $query = User::where('role', 'admin')->with(['events' => function($eventQuery) {
            $eventQuery->with(['category', 'participants'])
                      ->orderBy('created_at', 'desc');
        }]);

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('full_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Apply status filter
        // status filter removed (is_organizer dropped)

        $organizers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform data for response
        $transformedOrganizers = $organizers->getCollection()->map(function ($organizer) {
            $events = $organizer->events;

            // Calculate organizer revenue from transactions
            $organizerRevenue = Transaction::where('user_id', $organizer->id)
                ->where('type', 'payment_received')
                ->sum('amount');

            return [
                'id' => $organizer->id,
                'name' => $organizer->full_name ?? $organizer->name,
                'email' => $organizer->email,
                'phone' => $organizer->phone,
                'bio' => $organizer->bio,
                'avatar' => $organizer->avatar,
                'role' => $organizer->role,
                'status' => $organizer->suspended_at ? 'suspended' : 'active',
                'suspended_at' => $organizer->suspended_at,
                'created_at' => $organizer->created_at,
                'updated_at' => $organizer->updated_at,
                'events' => [
                    'total' => $events->count(),
                    'published' => $events->where('status', 'published')->count(),
                    'draft' => $events->where('status', 'draft')->count(),
                    'completed' => $events->where('status', 'completed')->count(),
                    'cancelled' => $events->where('status', 'cancelled')->count(),
                    'total_participants' => $events->sum('registered_count'),
                    'total_revenue' => $organizerRevenue,
                    'recent_events' => $events->take(5)->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'title' => $event->title,
                            'status' => $event->status,
                            'start_date' => $event->start_date,
                            'location' => $event->location,
                            'price' => $event->price,
                            'is_paid' => $event->is_paid,
                            'registered_count' => $event->registered_count,
                            'quota' => $event->quota,
                            'category' => [
                                'id' => $event->category->id,
                                'name' => $event->category->name,
                                'color' => $event->category->color,
                            ],
                            'created_at' => $event->created_at,
                        ];
                    })
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'organizers' => $transformedOrganizers,
                'pagination' => [
                    'current_page' => $organizers->currentPage(),
                    'last_page' => $organizers->lastPage(),
                    'per_page' => $organizers->perPage(),
                    'total' => $organizers->total(),
                    'from' => $organizers->firstItem(),
                    'to' => $organizers->lastItem(),
                    'has_more_pages' => $organizers->hasMorePages(),
                ]
            ]
        ]);
    }

    /**
     * Get all events from all organizers
     */
    public function getAllEvents(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $status = $request->get('status');
        $category = $request->get('category_id');
        $organizer = $request->get('organizer_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build query for all events
        $query = Event::with(['organizer', 'category', 'participants'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%')
                  ->orWhereHas('organizer', function($organizerQuery) use ($search) {
                      $organizerQuery->where('name', 'like', '%' . $search . '%')
                                   ->orWhere('full_name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        if ($organizer) {
            $query->where('user_id', $organizer);
        }

        if ($dateFrom) {
            $query->whereDate('start_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('start_date', '<=', $dateTo);
        }

        $events = $query->paginate($perPage);

        // Transform data for response
        $transformedEvents = $events->getCollection()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'price' => $event->price,
                'is_paid' => $event->is_paid,
                'quota' => $event->quota,
                'registered_count' => $event->registered_count,
                'status' => $event->status,
                'is_active' => $event->is_active,
                'image' => $event->image,
                'qr_code' => $event->qr_code,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
                'organizer' => [
                    'id' => $event->organizer->id,
                    'name' => $event->organizer->full_name ?? $event->organizer->name,
                    'email' => $event->organizer->email,
                    'phone' => $event->organizer->phone,
                    'role' => $event->organizer->role,
                ],
                'category' => [
                    'id' => $event->category->id,
                    'name' => $event->category->name,
                    'color' => $event->category->color,
                ],
                'participants' => [
                    'total' => $event->participants->count(),
                    'attended' => $event->participants->where('status', 'attended')->count(),
                    'registered' => $event->participants->where('status', 'registered')->count(),
                    'cancelled' => $event->participants->where('status', 'cancelled')->count(),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $transformedEvents,
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'from' => $events->firstItem(),
                    'to' => $events->lastItem(),
                    'has_more_pages' => $events->hasMorePages(),
                ]
            ]
        ]);
    }

    /**
     * Get statistics overview
     */
    public function getStatistics(Request $request): JsonResponse
    {
        // Get date range (default: last 12 months)
        $dateFrom = $request->get('date_from', now()->subMonths(12)->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        // Base queries
        $eventsQuery = Event::whereBetween('created_at', [$dateFrom, $dateTo]);
        $organizersQuery = User::where('role', 'admin')->whereBetween('created_at', [$dateFrom, $dateTo]);
        $participantsQuery = EventParticipant::whereBetween('created_at', [$dateFrom, $dateTo]);

        // Overall statistics - platform revenue from platform_fee transactions
        $totalRevenue = Transaction::where('type', 'platform_fee')->sum('amount');

        $statistics = [
            'total_organizers' => User::where('role', 'admin')->count(),
            'total_events' => Event::count(),
            'total_participants' => EventParticipant::count(),
            'total_revenue' => $totalRevenue,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];

        // Period statistics - platform revenue for the period
        $periodRevenue = Transaction::where('type', 'platform_fee')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $periodStats = [
            'organizers' => $organizersQuery->count(),
            'events' => $eventsQuery->count(),
            'participants' => $participantsQuery->count(),
            'revenue' => $periodRevenue,
        ];

        // Event status breakdown
        $eventStatusBreakdown = Event::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Monthly trends
        $monthlyTrends = Event::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top organizers by event count
        $topOrganizers = User::where('role', 'admin')
        ->withCount('events')
        ->orderBy('events_count', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($organizer) {
            return [
                'id' => $organizer->id,
                'name' => $organizer->full_name ?? $organizer->name,
                'email' => $organizer->email,
                'events_count' => $organizer->events_count,
            ];
        });

        // Category breakdown
        $categoryBreakdown = DB::table('events')
            ->join('categories', 'events.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, COUNT(events.id) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'period_statistics' => $periodStats,
                'event_status_breakdown' => $eventStatusBreakdown,
                'monthly_trends' => $monthlyTrends,
                'top_organizers' => $topOrganizers,
                'category_breakdown' => $categoryBreakdown,
            ]
        ]);
    }

    /**
     * Toggle organizer status
     */
    public function toggleOrganizerStatus(Request $request, User $user): JsonResponse
    {
        // Only allow toggling for admin users, not super_admin
        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify super admin status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'No status field to toggle (is_organizer removed)',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name ?? $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]
        ]);
    }

    /**
     * Get organizer details with all events
     */
    public function getOrganizerDetails(Request $request, User $organizer): JsonResponse
    {
        // Verify this is an organizer
        if (!$organizer->isOrganizer()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not an organizer'
            ], 400);
        }

        $events = $organizer->events()
            ->with(['category', 'participants'])
            ->orderBy('created_at', 'desc')
            ->get();

        $organizerData = [
            'id' => $organizer->id,
            'name' => $organizer->full_name ?? $organizer->name,
            'email' => $organizer->email,
            'phone' => $organizer->phone,
            'bio' => $organizer->bio,
            'avatar' => $organizer->avatar,
            'role' => $organizer->role,
            'role' => $organizer->role,
            'created_at' => $organizer->created_at,
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'price' => $event->price,
                    'is_paid' => $event->is_paid,
                    'quota' => $event->quota,
                    'registered_count' => $event->registered_count,
                    'status' => $event->status,
                    'is_active' => $event->is_active,
                    'category' => [
                        'id' => $event->category->id,
                        'name' => $event->category->name,
                        'color' => $event->category->color,
                    ],
                    'participants' => [
                        'total' => $event->participants->count(),
                        'attended' => $event->participants->where('status', 'attended')->count(),
                        'registered' => $event->participants->where('status', 'registered')->count(),
                        'cancelled' => $event->participants->where('status', 'cancelled')->count(),
                    ],
                    'created_at' => $event->created_at,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $organizerData
        ]);
    }

    /**
     * Suspend a user
     */
    public function suspendUser(Request $request, User $user): JsonResponse
    {
        // Prevent suspending super admin
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot suspend super admin'
            ], 403);
        }

        // Prevent self-suspension
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot suspend yourself'
            ], 403);
        }

        // Check if already suspended
        if ($user->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already suspended'
            ], 400);
        }

        $user->suspend();

        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name ?? $user->name,
                    'email' => $user->email,
                    'status' => 'suspended',
                    'suspended_at' => $user->suspended_at,
                ]
            ]
        ]);
    }

    /**
     * Activate a suspended user
     */
    public function activateUser(Request $request, User $user): JsonResponse
    {
        // Check if user is suspended
        if ($user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already active'
            ], 400);
        }

        $user->activate();

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name ?? $user->name,
                    'email' => $user->email,
                    'status' => 'active',
                    'suspended_at' => null,
                ]
            ]
        ]);
    }

    /**
     * Delete a user (soft delete)
     */
    public function deleteUser(Request $request, User $user): JsonResponse
    {
        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete super admin'
            ], 403);
        }

        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ], 403);
        }

        $userName = $user->full_name ?? $user->name;
        $userEmail = $user->email;

        // Perform soft delete
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            'data' => [
                'deleted_user' => [
                    'name' => $userName,
                    'email' => $userEmail,
                ]
            ]
        ]);
    }
}