<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Category;
use App\Models\EventParticipant;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalyticsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalyticsController extends Controller
{
    /**
     * Get analytics dashboard data for admin organizer
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $dateRange = $request->get('date_range', 30);
            $startDate = Carbon::now()->subDays($dateRange);
            $endDate = Carbon::now();

            $organizerId = auth()->id();
            $eventIds = Event::where('user_id', $organizerId)->pluck('id');

            // === Stats (Key Metrics) ===
            $totalRevenue = (float) Event::where('user_id', $organizerId)->sum('price');
            $avgRating = (float) Feedback::whereIn('event_id', $eventIds)->avg('rating') ?? 0;
            $totalEvents = Event::where('user_id', $organizerId)->count();
            $totalParticipants = EventParticipant::whereIn('event_id', $eventIds)->count();
            $conversionRate = $totalEvents > 0 ? round(($totalParticipants / $totalEvents) * 100, 1) : 0;
            $activeUsers = EventParticipant::whereIn('event_id', $eventIds)->distinct('user_id')->count();
            $newUsers = EventParticipant::whereIn('event_id', $eventIds)
                ->where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count();
            $totalFeedbacks = Feedback::whereIn('event_id', $eventIds)->count();

            // === Monthly Trends (12 bulan terakhir) ===
            $monthlyTrends = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $eventsCount = Event::where('user_id', $organizerId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $monthlyTrends[] = [
                    'month' => $date->format('M Y'),
                    'events' => $eventsCount,
                ];
            }

            // === Category Analytics ===
            $categoryAnalytics = Category::withCount([
                'events' => function($query) use ($startDate, $organizerId) {
                    $query->where('start_date', '>=', $startDate)
                          ->where('user_id', $organizerId);
                }
            ])
            ->having('events_count', '>', 0)
            ->orderBy('events_count', 'desc')
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'events_count' => (int) $category->events_count,
                    'color' => $category->color ?? '#3B82F6',
                ];
            });

            // === User Trends (registrasi harian) ===
            $userTrends = EventParticipant::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT user_id) as count')
            )
            ->whereIn('event_id', $eventIds)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count
            ]);

            // === Event Trends (event dibuat harian) ===
            $eventTrends = Event::where('user_id', $organizerId)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'count' => (int) $item->count
                ]);

            // === Revenue Trends (harian) ===
            $revenueTrends = Event::where('user_id', $organizerId)
                ->where('status', 'published')
                ->where('start_date', '>=', $startDate)
                ->select(
                    DB::raw('DATE(start_date) as date'),
                    DB::raw('SUM(price) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue
                ]);

            // === Top Events ===
            $topEvents = Event::where('user_id', $organizerId)
                ->withCount('participants')
                ->with('category:id,name,color')
                ->where('start_date', '>=', $startDate)
                ->orderBy('participants_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($event) {
                    return [
                        'title' => $event->title,
                        'participants_count' => $event->participants_count,
                        'price' => (float) $event->price,
                        'category' => [
                            'name' => $event->category->name,
                            'color' => $event->category->color ?? '#3B82F6',
                        ],
                    ];
                });

            // === Response ===
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_events' => $totalEvents,
                        'active_events' => Event::where('user_id', $organizerId)->where('status', 'published')->count(),
                        'total_revenue' => $totalRevenue,
                        'avg_rating' => round($avgRating, 1),
                        'conversion_rate' => $conversionRate,
                        'active_users' => $activeUsers,
                        'new_users' => $newUsers,
                        'total_participants' => $totalParticipants,
                        'total_feedbacks' => $totalFeedbacks,
                        'avg_attendance' => $totalEvents > 0 ? round($totalParticipants / $totalEvents, 1) : 0,
                    ],
                    'monthlyTrends' => $monthlyTrends,
                    'categoryAnalytics' => $categoryAnalytics,
                    'userAnalytics' => [
                        'user_trends' => $userTrends,
                    ],
                    'eventAnalytics' => [
                        'event_trends' => $eventTrends,
                    ],
                    'revenueAnalytics' => [
                        'revenue_trends' => $revenueTrends,
                    ],
                    'topEvents' => $topEvents,
                    'date_range' => [
                        'days' => (int) $dateRange,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


/**
 * Export analytics data
 */
/**
 * Export analytics data as PDF
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function export(Request $request)
{
    try {
        // Hanya izinkan PDF (tidak perlu validasi format)
        $dateRange = (int) $request->get('date_range', 30);
        $startDate = Carbon::now()->subDays($dateRange);
        $endDate = Carbon::now();
        $organizerId = auth()->id();
        $eventIds = Event::where('user_id', $organizerId)->pluck('id');

        // === Ambil data ===
        $stats = [
            'total_revenue' => (float) Event::where('user_id', $organizerId)->sum('price'),
            'avg_rating' => (float) (Feedback::whereIn('event_id', $eventIds)->avg('rating') ?? 0),
            'total_events' => Event::where('user_id', $organizerId)->count(),
            'total_participants' => EventParticipant::whereIn('event_id', $eventIds)->count(),
            'new_users' => EventParticipant::whereIn('event_id', $eventIds)
                ->where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count(),
            'total_feedbacks' => Feedback::whereIn('event_id', $eventIds)->count(),
        ];

        $topEvents = Event::where('user_id', $organizerId)
            ->withCount('participants')
            ->with('category:id,name,color')
            ->where('start_date', '>=', $startDate)
            ->orderBy('participants_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'title' => $event->title,
                    'participants_count' => $event->participants_count,
                    'price' => (float) $event->price,
                    'category' => [
                        'name' => $event->category->name,
                        'color' => $event->category->color ?? '#3B82F6',
                    ],
                ];
            });

        // === Generate & simpan PDF ===
        $pdf = PDF::loadView('exports.analytics-pdf', compact('stats', 'topEvents'));
        $filename = "analytics_export_" . now()->format('Y-m-d_His') . ".pdf";
        $path = "exports/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'success' => true,
            'message' => 'PDF exported successfully',
            'data' => [
                'url' => $url,
                'filename' => $filename
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to export PDF',
            'error' => $e->getMessage()
        ], 500);
    }
}
}