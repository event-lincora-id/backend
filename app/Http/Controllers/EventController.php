<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventController extends Controller
{
    public function __construct()
    {
        // Role validation is handled by middleware in routes
    }

    /**
     * Display a listing of events
     */
    public function index(Request $request)
    {
        $organizerId = auth()->id();
        $query = Event::with(['category', 'organizer']);

        // If not super admin, limit to current organizer's events
        if (!(auth()->check() && method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin())) {
            $query->where('user_id', $organizerId); // Only organizer's events
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // For super admin: allow filter by organizer
        if ($request->has('organizer_id') && $request->organizer_id) {
            $query->where('user_id', $request->organizer_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(10);
        $categories = Category::all();

        return view('admin.events', compact('events', 'categories'));
    }

    /**
     * Show the form for creating a new event
     */
    public function create()
    {
        $categories = Category::all();
        $organizers = User::where('role', 'admin')->get(); // Admin = Event Organizer
        
        return view('admin.events.create', compact('categories', 'organizers'));
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'event_type' => 'nullable|in:offline,online,hybrid',
            'contact_info' => 'nullable|string',
            'requirements' => 'nullable|string',
            'quota' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,cancelled,completed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        $data['user_id'] = auth()->id(); // Set organizer as event creator
        
        // Set is_paid based on price
        $data['is_paid'] = $request->price > 0;
        
        // Rename max_participants to quota
        if (isset($data['max_participants'])) {
            $data['quota'] = $data['max_participants'];
            unset($data['max_participants']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('events', 'public');
            $data['image'] = $imagePath;
        }

        // Generate QR code
        $qrCodeString = 'event_' . time() . '_' . uniqid();
        
        // Store QR code string first
        $data['qr_code'] = $qrCodeString;

        $event = Event::create($data);

        // Create directory if it doesn't exist
        $qrCodeDir = storage_path('app/public/qr_codes');
        if (!is_dir($qrCodeDir)) {
            mkdir($qrCodeDir, 0755, true);
        }

        // Generate QR code image (using SVG format for better compatibility)
        $qrCodePath = 'qr_codes/' . $qrCodeString . '.svg';
        QrCode::format('svg')->size(200)->generate($qrCodeString, storage_path('app/public/' . $qrCodePath));
        
        // Update with image path for display, but keep original string for searching
        // We'll store both: use qr_code for display path, but search works with basename
        $event->update(['qr_code' => $qrCodePath]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully!');
    }

    /**
     * Display the specified event
     */
    public function show(Event $event)
    {
        $event->load(['category', 'organizer', 'participants.user']);
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event
     */
    public function edit(Event $event)
    {
        $categories = Category::all();
        $organizers = User::where('role', 'admin')->get(); // Admin = Event Organizer
        
        return view('admin.events.edit', compact('event', 'categories', 'organizers'));
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'event_type' => 'nullable|in:offline,online,hybrid',
            'contact_info' => 'nullable|string',
            'requirements' => 'nullable|string',
            'quota' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,cancelled,completed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->all();
        $data['user_id'] = auth()->id(); // Ensure organizer owns the event
        
        // Set is_paid based on price
        $data['is_paid'] = $request->price > 0;
        
        // Rename max_participants to quota if exists
        if (isset($data['max_participants'])) {
            $data['quota'] = $data['max_participants'];
            unset($data['max_participants']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            
            $imagePath = $request->file('image')->store('events', 'public');
            $data['image'] = $imagePath;
        }

        $event->update($data);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event
     */
    public function destroy(Event $event)
    {
        // Delete associated image
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully!');
    }

    /**
     * Toggle event status
     */
    public function toggleStatus(Event $event)
    {
        $statuses = ['draft', 'published', 'cancelled', 'completed'];
        $currentIndex = array_search($event->status, $statuses);
        $nextIndex = ($currentIndex + 1) % count($statuses);
        
        $event->update(['status' => $statuses[$nextIndex]]);

        return redirect()->back()
            ->with('success', "Event status changed to {$statuses[$nextIndex]}!");
    }

    /**
     * Get event participants
     */
    public function participants(Event $event)
    {
        $participants = $event->participants()->with('user')->paginate(10);
        return view('admin.events.participants', compact('event', 'participants'));
    }
}

