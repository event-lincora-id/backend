<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventUpdateNotificationMail;

class EventController extends Controller
{
    /**
     * Get all published events (Homepage) with advanced search & filter
     * By default shows only upcoming events. Pass ?include_past=true to show all events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['organizer', 'category'])
            ->active();

        // Filter by status - include both published and completed events when include_past is true
        // Only published events when include_past is false
        if ($request->boolean('include_past')) {
            $query->whereIn('status', ['published', 'completed']);
        } else {
            $query->published();
        }

        // Only filter by upcoming unless explicitly requested to include past events
        if (!$request->boolean('include_past')) {
            $query->upcoming();
        }

        $query->open();

        // Advanced search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('location', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('organizer', function($organizerQuery) use ($searchTerm) {
                      $organizerQuery->where('name', 'like', '%' . $searchTerm . '%')
                                   ->orWhere('full_name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by multiple categories
        if ($request->has('category_ids') && is_array($request->category_ids)) {
            $query->whereIn('category_id', $request->category_ids);
        }

        // Filter by paid/free - based on price, not is_paid column
        if ($request->has('is_paid') && $request->is_paid !== '' && $request->is_paid !== null) {
            if ($request->is_paid == '0' || $request->is_paid === 'false' || $request->is_paid === false) {
                // Free events: price = 0
                $query->where('price', 0);
            } elseif ($request->is_paid == '1' || $request->is_paid === 'true' || $request->is_paid === true) {
                // Paid events: price > 0
                $query->where('price', '>', 0);
            }
        }

        // Filter by price range
        if ($request->has('price_min') && is_numeric($request->price_min)) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max') && is_numeric($request->price_max)) {
            $query->where('price', '<=', $request->price_max);
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('start_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->has('location') && !empty($request->location)) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by availability (quota)
        if ($request->has('available_only') && $request->boolean('available_only')) {
            $query->whereRaw('quota > registered_count');
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('registered_count', 'desc');
                break;
            default:
                $query->orderBy('start_date', $sortOrder);
        }

        $perPage = min($request->get('per_page', 10), 50); // Max 50 per page
        $events = $query->paginate($perPage);

        // Add search metadata
        $searchMetadata = [
            'search_term' => $request->get('search'),
            'filters_applied' => array_filter([
                'category_id' => $request->get('category_id'),
                'is_paid' => $request->has('is_paid') ? $request->boolean('is_paid') : null,
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'location' => $request->get('location'),
                'available_only' => $request->boolean('available_only'),
            ]),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];

        return response()->json([
            'success' => true,
            'data' => $events,
            'search_metadata' => $searchMetadata
        ]);
    }

    /**
     * Get event details
     */
    public function show(Event $event): JsonResponse
    {
        // Security check: Draft events can only be viewed by their organizer
        if ($event->status === 'draft') {
            $user = auth('sanctum')->user();

            // If not authenticated or not the organizer, return 404
            if (!$user || $user->id !== $event->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }
        }

        $event->load(['organizer', 'category', 'participants']);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Create new event (Organizer only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->canCreateEvents()) {
            return response()->json([
                'success' => false,
                'message' => 'Only organizers (admins) can create events'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_type' => 'required|in:offline,online,hybrid',
            'location' => 'required_if:event_type,offline,hybrid|nullable|string|max:255',
            'meeting_link' => 'required_if:event_type,online,hybrid|nullable|url|max:500',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'is_paid' => 'boolean',
            'price' => 'required_if:is_paid,true|numeric|min:0',
            'quota' => 'nullable|integer|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $user->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        // Generate QR code string
        $qrCodeString = 'event_' . time() . '_' . uniqid();
        $data['qr_code_string'] = $qrCodeString;

        // Keep qr_code field for backward compatibility (but no longer generate PNG)
        $data['qr_code'] = $qrCodeString;

        $event = Event::create($data);

        $event->load(['organizer', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201);
    }

    /**
     * Update event (Organizer only)
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($event->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this event'
            ], 403);
        }

        // Check if event has already started - cannot edit started events
        if ($event->start_date <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit events that have already started',
                'event_start_date' => $event->start_date,
                'current_time' => now()
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'event_type' => 'sometimes|required|in:offline,online,hybrid',
            'location' => 'required_if:event_type,offline,hybrid|nullable|string|max:255',
            'meeting_link' => 'required_if:event_type,online,hybrid|nullable|url|max:500',
            'start_date' => 'sometimes|required|date|after:now',
            'end_date' => 'sometimes|required|date|after:start_date',
            'is_paid' => 'sometimes|boolean',
            'price' => 'required_if:is_paid,true|numeric|min:0',
            'quota' => 'nullable|integer|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'sometimes|in:draft,published,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Capture old values before update for change detection
        $oldValues = [
            'location' => $event->location,
            'meeting_link' => $event->meeting_link,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);
        $event->load(['organizer', 'category']);

        // Detect changes and notify participants
        $changes = [];

        if ($oldValues['location'] !== $event->location && $event->location) {
            $changes[] = [
                'field' => 'location',
                'label' => 'Event Location',
                'old' => $oldValues['location'] ?: 'Not set',
                'new' => $event->location,
            ];
        }

        if ($oldValues['meeting_link'] !== $event->meeting_link && $event->meeting_link) {
            $changes[] = [
                'field' => 'meeting_link',
                'label' => 'Meeting Link',
                'old' => $oldValues['meeting_link'] ?: 'Not set',
                'new' => $event->meeting_link,
            ];
        }

        if ($oldValues['start_date'] && !$oldValues['start_date']->eq($event->start_date)) {
            $changes[] = [
                'field' => 'start_date',
                'label' => 'Start Date & Time',
                'old' => $oldValues['start_date']->format('l, d F Y \a\t H:i'),
                'new' => $event->start_date->format('l, d F Y \a\t H:i'),
            ];
        }

        if ($oldValues['end_date'] && !$oldValues['end_date']->eq($event->end_date)) {
            $changes[] = [
                'field' => 'end_date',
                'label' => 'End Date & Time',
                'old' => $oldValues['end_date']->format('l, d F Y \a\t H:i'),
                'new' => $event->end_date->format('l, d F Y \a\t H:i'),
            ];
        }

        // Send email notifications to all participants if there are changes
        if (!empty($changes)) {
            $participants = $event->participants()
                ->with('user')
                ->whereIn('payment_status', ['paid', 'free'])
                ->get();

            foreach ($participants as $participant) {
                if ($participant->user && $participant->user->email) {
                    try {
                        Mail::to($participant->user->email)->send(
                            new EventUpdateNotificationMail($event, $changes, $participant->user->name)
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to send event update email: ' . $e->getMessage());
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event,
            'notifications_sent' => count($changes) > 0 ? true : false,
        ]);
    }

    /**
     * Delete event (Organizer only)
     */
    public function destroy(Event $event): JsonResponse
    {
        $user = request()->user();

        if ($event->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this event'
            ], 403);
        }

        // Check if event has participants - cannot delete events with participants
        if ($event->registered_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete events with registered participants',
                'registered_count' => $event->registered_count
            ], 400);
        }

        // Delete associated files
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }
        // No longer delete qr_code as it's just a string now, not a file
        // Keep for backward compatibility with old events that still have PNG files
        if ($event->qr_code && str_contains($event->qr_code, '.png')) {
            Storage::disk('public')->delete($event->qr_code);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }

    /**
     * Get user's events (My Events)
     */
    public function myEvents(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Event::with(['organizer', 'category'])
            ->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Get events user is participating in
     */
    public function participatingEvents(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Event::with(['organizer', 'category'])
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($request->has('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'past') {
                $query->where('end_date', '<', now());
            }
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Advanced search and filter for events
     */
    public function search(Request $request): JsonResponse
    {
        $query = Event::with(['organizer', 'category'])
            ->published()
            ->active()
            ->upcoming()
            ->open();

        // Advanced search with multiple fields
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('location', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('organizer', function($organizerQuery) use ($searchTerm) {
                      $organizerQuery->where('name', 'like', '%' . $searchTerm . '%')
                                   ->orWhere('full_name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Apply all filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        $perPage = min($request->get('per_page', 12), 50);
        $events = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events,
            'filters' => $this->getAvailableFilters($request)
        ]);
    }

    /**
     * Get filter options for search
     */
    public function getFilterOptions(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        $priceRanges = [
            ['min' => 0, 'max' => 0, 'label' => 'Free'],
            ['min' => 1, 'max' => 100000, 'label' => 'Under 100K'],
            ['min' => 100000, 'max' => 500000, 'label' => '100K - 500K'],
            ['min' => 500000, 'max' => 1000000, 'label' => '500K - 1M'],
            ['min' => 1000000, 'max' => null, 'label' => 'Above 1M'],
        ];

        $dateRanges = [
            ['key' => 'today', 'label' => 'Today'],
            ['key' => 'tomorrow', 'label' => 'Tomorrow'],
            ['key' => 'this_week', 'label' => 'This Week'],
            ['key' => 'next_week', 'label' => 'Next Week'],
            ['key' => 'this_month', 'label' => 'This Month'],
            ['key' => 'next_month', 'label' => 'Next Month'],
        ];

        $sortOptions = [
            ['key' => 'start_date', 'label' => 'Date (Earliest First)', 'order' => 'asc'],
            ['key' => 'start_date', 'label' => 'Date (Latest First)', 'order' => 'desc'],
            ['key' => 'price', 'label' => 'Price (Low to High)', 'order' => 'asc'],
            ['key' => 'price', 'label' => 'Price (High to Low)', 'order' => 'desc'],
            ['key' => 'title', 'label' => 'Title (A-Z)', 'order' => 'asc'],
            ['key' => 'title', 'label' => 'Title (Z-A)', 'order' => 'desc'],
            ['key' => 'popularity', 'label' => 'Most Popular', 'order' => 'desc'],
            ['key' => 'created_at', 'label' => 'Newest First', 'order' => 'desc'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'price_ranges' => $priceRanges,
                'date_ranges' => $dateRanges,
                'sort_options' => $sortOptions,
            ]
        ]);
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearches(): JsonResponse
    {
        // This could be enhanced with actual search analytics
        $popularSearches = [
            'Technology',
            'Business',
            'Education',
            'Workshop',
            'Conference',
            'Seminar',
            'Training',
            'Networking',
            'Startup',
            'Marketing',
        ];

        return response()->json([
            'success' => true,
            'data' => $popularSearches
        ]);
    }

    /**
     * Get categories for filtering
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request): void
    {
        // Category filters
        if ($request->has('category_ids') && is_array($request->category_ids)) {
            $query->whereIn('category_id', $request->category_ids);
        } elseif ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Price filters
        if ($request->has('price_min') && is_numeric($request->price_min)) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->has('price_max') && is_numeric($request->price_max)) {
            $query->where('price', '<=', $request->price_max);
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        // Location filter
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Paid/Free filter
        if ($request->has('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // Availability filter
        if ($request->has('available_only') && $request->boolean('available_only')) {
            $query->whereRaw('quota > registered_count');
        }

        // Quick date filters
        if ($request->has('date_filter')) {
            $this->applyDateFilter($query, $request->date_filter);
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('registered_count', 'desc');
                break;
            default:
                $query->orderBy('start_date', $sortOrder);
        }
    }

    /**
     * Apply quick date filters
     */
    private function applyDateFilter($query, string $dateFilter): void
    {
        switch ($dateFilter) {
            case 'today':
                $query->whereDate('start_date', today());
                break;
            case 'tomorrow':
                $query->whereDate('start_date', today()->addDay());
                break;
            case 'this_week':
                $query->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'next_week':
                $query->whereBetween('start_date', [now()->addWeek()->startOfWeek(), now()->addWeek()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'next_month':
                $query->whereBetween('start_date', [now()->addMonth()->startOfMonth(), now()->addMonth()->endOfMonth()]);
                break;
        }
    }

    /**
     * Get available filters based on current results
     */
    private function getAvailableFilters(Request $request): array
    {
        $baseQuery = Event::published()->active()->upcoming()->open();
        
        // Apply current filters except the one we're checking
        $this->applyFilters($baseQuery, $request);

        $availableCategories = Category::whereIn('id', 
            $baseQuery->pluck('category_id')
        )->where('is_active', true)->get(['id', 'name', 'color']);

        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return [
            'categories' => $availableCategories,
            'price_range' => [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 0,
            ],
        ];
    }
}
