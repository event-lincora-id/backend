@extends('admin.layout')

@section('title', 'Edit Event')
@section('page-title', 'Edit Event')
@section('page-description', 'Update event information')

@section('content')
<div class="bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Edit Event: {{ $event->title }}</h3>
            <div class="flex space-x-2">
                <a href="{{ route('admin.events.show', $event) }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-eye mr-2"></i>View Event
                </a>
                <a href="{{ route('admin.events.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Events
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Event Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Event Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $event->title) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-500 @enderror" 
                           required>
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                    <textarea name="description" id="description" rows="4" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror" 
                              required>{{ old('description', $event->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                    <select name="category_id" id="category_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('category_id') border-red-500 @enderror" 
                            required>
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $event->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Location *</label>
                    <input type="text" name="location" id="location" value="{{ old('location', $event->location) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('location') border-red-500 @enderror" 
                           placeholder="e.g., Jakarta Convention Center, Online via Zoom" required>
                    @error('location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Event Type -->
                <div>
                    <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                    <select name="event_type" id="event_type" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('event_type') border-red-500 @enderror">
                        <option value="offline" {{ old('event_type', $event->event_type) == 'offline' ? 'selected' : '' }}>Offline (Physical Event)</option>
                        <option value="online" {{ old('event_type', $event->event_type) == 'online' ? 'selected' : '' }}>Online (Virtual Event)</option>
                        <option value="hybrid" {{ old('event_type', $event->event_type) == 'hybrid' ? 'selected' : '' }}>Hybrid (Both Online & Offline)</option>
                    </select>
                    @error('event_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Meeting Link (for Online/Hybrid Events) -->
                <div id="meeting-link-container">
                    <label for="meeting_link" class="block text-sm font-medium text-gray-700">
                        Meeting Link <span class="text-xs text-gray-500">(for Online/Hybrid events)</span>
                    </label>
                    <input type="url" name="meeting_link" id="meeting_link" value="{{ old('meeting_link', $event->meeting_link) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('meeting_link') border-red-500 @enderror"
                           placeholder="https://meet.google.com/xxx or https://zoom.us/j/xxx">
                    <p class="mt-1 text-xs text-gray-500">Enter the Zoom, Google Meet, Teams, or other meeting platform link</p>
                    @error('meeting_link')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date and Time -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                        <input type="datetime-local" name="start_date" id="start_date" 
                               value="{{ old('start_date', \Carbon\Carbon::parse($event->start_date)->format('Y-m-d\TH:i')) }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-500 @enderror" 
                               required>
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date *</label>
                        <input type="datetime-local" name="end_date" id="end_date" 
                               value="{{ old('end_date', \Carbon\Carbon::parse($event->end_date)->format('Y-m-d\TH:i')) }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-500 @enderror" 
                               required>
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Max Participants -->
                <div>
                    <label for="quota" class="block text-sm font-medium text-gray-700">Max Participants (Quota) *</label>
                    <input type="number" name="quota" id="quota" value="{{ old('quota', $event->quota) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('quota') border-red-500 @enderror" 
                           min="1" required>
                    @error('quota')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price (IDR) *</label>
                    <input type="number" name="price" id="price" value="{{ old('price', $event->price) }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('price') border-red-500 @enderror" 
                           min="0" step="1000" required>
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                    <select name="status" id="status" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror" 
                            required>
                        <option value="draft" {{ old('status', $event->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $event->status) == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="cancelled" {{ old('status', $event->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="completed" {{ old('status', $event->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Image -->
                @if($event->image)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->title }}" class="w-full h-48 object-cover rounded-lg border">
                </div>
                @endif

                <!-- New Event Image -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">
                        {{ $event->image ? 'Replace Image' : 'Event Image' }}
                    </label>
                    <input type="file" name="image" id="image" accept="image/*" 
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Image Preview -->
                <div id="image-preview" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Image Preview</label>
                    <img id="preview-img" class="w-full h-48 object-cover rounded-lg border" alt="Preview">
                </div>

                <!-- Contact Information -->
                <div>
                    <label for="contact_info" class="block text-sm font-medium text-gray-700">Contact Information</label>
                    <textarea name="contact_info" id="contact_info" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('contact_info') border-red-500 @enderror" 
                              placeholder="Phone: +62xxx, Email: contact@example.com, WhatsApp: +62xxx">{{ old('contact_info', $event->contact_info) }}</textarea>
                    @error('contact_info')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Requirements -->
                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700">Requirements</label>
                    <textarea name="requirements" id="requirements" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('requirements') border-red-500 @enderror" 
                              placeholder="e.g., Bring laptop, Valid ID, Dress code: Business casual">{{ old('requirements', $event->requirements) }}</textarea>
                    @error('requirements')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8 flex justify-end space-x-4">
            <a href="{{ route('admin.events.show', $event) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Update Event
            </button>
        </div>
    </form>
</div>

<script>
// Image preview functionality
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('image-preview').classList.add('hidden');
    }
});

// Date validation
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    const endDateInput = document.getElementById('end_date');
    endDateInput.min = this.value;

    if (endDateInput.value && new Date(endDateInput.value) <= startDate) {
        endDateInput.value = '';
    }
});

// Meeting link field is always visible
// Backend validation will handle required logic based on event type
</script>
@endsection
