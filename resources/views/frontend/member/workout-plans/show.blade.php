@extends('frontend.layouts.app')

@push('scripts')
<script>
let mediaRecorders = {};
let recordedBlobs = {};
let recordingTimers = {};

async function startRecording(index, exerciseName) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' }, 
            audio: true 
        });
        
        const preview = document.getElementById(`preview-${index}`);
        const stopBtn = document.getElementById(`stop-btn-${index}`);
        const uploadBtn = document.getElementById(`upload-btn-${index}`);
        const recordingStatus = document.getElementById(`recording-status-${index}`);
        const timer = document.getElementById(`timer-${index}`);
        
        preview.srcObject = stream;
        preview.classList.remove('hidden');
        stopBtn.classList.remove('hidden');
        recordingStatus.classList.remove('hidden');
        uploadBtn.classList.add('hidden');
        
        const options = { mimeType: 'video/webm;codecs=vp9,opus' };
        const mediaRecorder = new MediaRecorder(stream, options);
        mediaRecorders[index] = mediaRecorder;
        recordedBlobs[index] = [];
        
        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                recordedBlobs[index].push(event.data);
            }
        };
        
        let seconds = 0;
        recordingTimers[index] = setInterval(() => {
            seconds++;
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            timer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
            
            // Auto-stop at 60 seconds (1 minute)
            if (seconds >= 60) {
                stopRecording(index);
            }
        }, 1000);
        
        mediaRecorder.start(1000); // Collect data every second
    } catch (error) {
        console.error('Error accessing camera:', error);
        alert('Error accessing camera. Please allow camera permissions and try again.');
    }
}

function stopRecording(index) {
    const mediaRecorder = mediaRecorders[index];
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        
        const preview = document.getElementById(`preview-${index}`);
        const stopBtn = document.getElementById(`stop-btn-${index}`);
        const uploadBtn = document.getElementById(`upload-btn-${index}`);
        const recordingStatus = document.getElementById(`recording-status-${index}`);
        
        preview.classList.add('hidden');
        stopBtn.classList.add('hidden');
        recordingStatus.classList.add('hidden');
        uploadBtn.classList.remove('hidden');
        
        if (recordingTimers[index]) {
            clearInterval(recordingTimers[index]);
            delete recordingTimers[index];
        }
        
        // Create blob URL for preview
        const blob = new Blob(recordedBlobs[index], { type: 'video/webm' });
        const url = URL.createObjectURL(blob);
        preview.srcObject = null;
        preview.src = url;
        preview.classList.remove('hidden');
        preview.controls = true;
    }
}

async function uploadVideo(index, exerciseName, workoutPlanId) {
    if (!recordedBlobs[index] || recordedBlobs[index].length === 0) {
        alert('Please record a video first.');
        return;
    }
    
    const uploadBtn = document.getElementById(`upload-btn-${index}`);
    const uploadStatus = document.getElementById(`upload-status-${index}`);
    
    uploadBtn.disabled = true;
    
    // Create progress bar
    uploadStatus.className = 'mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg';
    uploadStatus.innerHTML = `
        <div class="mb-2">
            <div class="flex justify-between items-center mb-1">
                <span class="text-sm font-medium text-blue-800">Uploading video...</span>
                <span class="text-sm font-semibold text-blue-800" id="upload-progress-text-${index}">0%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div id="upload-progress-bar-${index}" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    `;
    uploadStatus.classList.remove('hidden');
    
    const progressBar = document.getElementById(`upload-progress-bar-${index}`);
    const progressText = document.getElementById(`upload-progress-text-${index}`);
    
    try {
        const blob = new Blob(recordedBlobs[index], { type: 'video/webm' });
        const fileName = `${exerciseName}-${Date.now()}.webm`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const uploadUrl = `/member/workout-plans/${workoutPlanId}/upload-video`;
        const chunkedUploadUrl = `/member/workout-plans/${workoutPlanId}/upload-video-chunk`;
        
        // Use reusable upload utility with additional data
        const response = await videoUploader.upload(
            blob,
            uploadUrl,
            chunkedUploadUrl,
            csrfToken,
            {
                onProgress: (percent, loaded, total) => {
                    progressBar.style.width = percent + '%';
                    progressText.textContent = Math.round(percent) + '%';
                },
                onCompressStart: () => {
                    uploadStatus.innerHTML = `
                        <div class="mb-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-blue-800">Compressing video...</span>
                                <span class="text-sm font-semibold text-blue-800">Please wait</span>
                            </div>
                        </div>
                    `;
                },
                onCompressEnd: () => {
                    uploadStatus.innerHTML = `
                        <div class="mb-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-blue-800">Uploading video...</span>
                                <span class="text-sm font-semibold text-blue-800" id="upload-progress-text-${index}">0%</span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-2">
                                <div id="upload-progress-bar-${index}" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                    `;
                },
                additionalData: {
                    exercise_name: exerciseName,
                    duration_seconds: 60
                }
            }
        );
        
        uploadStatus.className = 'mt-4 p-4 bg-green-50 border border-green-200 rounded-lg';
        uploadStatus.innerHTML = `
            <div class="flex items-center text-green-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-semibold">Video uploaded successfully. Waiting for trainer approval.</span>
            </div>
        `;
        
        // Reload page after 2 seconds
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    } catch (error) {
        uploadStatus.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
        uploadStatus.innerHTML = `
            <div class="flex items-center text-red-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-semibold">Error: ${error.message}</span>
            </div>
        `;
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Upload Video
        `;
    }
}

// Initialize video upload utility
const videoUploader = new VideoUploadUtils({
    chunkSize: 5 * 1024 * 1024, // 5MB chunks
    compressionThreshold: 20 * 1024 * 1024, // 20MB
    chunkedThreshold: 10 * 1024 * 1024, // 10MB
});
</script>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('member.workout-plans') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Workout Plans
            </a>
        </div>

        {{-- Plan Header Card --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-8 mb-6 text-white">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold mb-2">{{ $workoutPlan->plan_name ?? 'Untitled Plan' }}</h1>
                    @if($workoutPlan->description)
                        <p class="text-green-100 text-lg">{{ $workoutPlan->description }}</p>
                    @endif
                </div>
                <span class="px-4 py-2 bg-white bg-opacity-20 backdrop-blur-sm rounded-full text-sm font-semibold">
                    {{ ucfirst($workoutPlan->status ?? 'active') }}
                </span>
            </div>
        </div>

        {{-- Plan Details Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Plan Information Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Plan Information
                </h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-500">Duration</span>
                        <span class="text-sm font-semibold text-gray-900">
                            @if($durationMinutes > 0)
                                {{ $durationMinutes }} minutes per session
                            @elseif($workoutPlan->duration_weeks)
                                {{ $workoutPlan->duration_weeks }} {{ $workoutPlan->duration_weeks == 1 ? 'week' : 'weeks' }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-500">Difficulty</span>
                        <span class="px-3 py-1 {{ $difficultyColor }} text-xs font-semibold rounded-full">
                            {{ $difficulty }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-500">Total Exercises</span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $exerciseCount }} {{ $exerciseCount == 1 ? 'exercise' : 'exercises' }}
                        </span>
                    </div>
                    @if($workoutPlan->trainer)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-500">Trainer</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $workoutPlan->trainer->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm font-medium text-gray-500">Start Date</span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $workoutPlan->start_date ? format_date($workoutPlan->start_date) : 'N/A' }}
                        </span>
                    </div>
                    @if($workoutPlan->end_date)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm font-medium text-gray-500">End Date</span>
                        <span class="text-sm font-semibold text-gray-900">{{ format_date($workoutPlan->end_date) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Progress Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Plan Progress
                </h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Status</span>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                {{ $workoutPlan->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $workoutPlan->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $workoutPlan->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $workoutPlan->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                {{ !in_array($workoutPlan->status, ['active', 'completed', 'paused', 'cancelled']) ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($workoutPlan->status ?? 'active') }}
                            </span>
                        </div>
                    </div>
                    @if($workoutPlan->start_date && $workoutPlan->end_date)
                    @php
                        $startDate = \Carbon\Carbon::parse($workoutPlan->start_date)->startOfDay();
                        $endDate = \Carbon\Carbon::parse($workoutPlan->end_date)->endOfDay();
                        $now = \Carbon\Carbon::now()->startOfDay();
                        
                        // Calculate total days in the plan
                        $totalDays = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
                        
                        // Get days that have passed (from start to now or end date, whichever is earlier)
                        $daysPassed = 0;
                        $currentCheckDate = $startDate->copy();
                        $attendedCount = 0;
                        $missedCount = 0;
                        $daysBreakdown = [];
                        
                        if ($now->gte($startDate)) {
                            // Plan has started - check each day
                            $checkUntil = $now->lt($endDate) ? $now : $endDate;
                            
                            while ($currentCheckDate->lte($checkUntil)) {
                                $dayKey = $currentCheckDate->format('Y-m-d');
                                $hasAttended = in_array($dayKey, $attendedDates ?? []);
                                
                                $daysBreakdown[] = [
                                    'date' => $currentCheckDate->copy(),
                                    'attended' => $hasAttended,
                                ];
                                
                                if ($hasAttended) {
                                    $attendedCount++;
                                } else {
                                    $missedCount++;
                                }
                                
                                $daysPassed++;
                                $currentCheckDate->addDay();
                            }
                        }
                        
                        // Calculate progress based on attendance
                        $attendanceProgress = $daysPassed > 0 ? ($attendedCount / $daysPassed) * 100 : 0;
                        $overallProgress = $totalDays > 0 ? ($daysPassed / $totalDays) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Attendance Progress</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($attendanceProgress, 1) }}%</span>
                        </div>
                        
                        {{-- Visual Progress Bar with Green/Red --}}
                        @if($daysPassed > 0)
                        <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden flex">
                            @foreach($daysBreakdown as $day)
                                <div class="flex-1 h-full {{ $day['attended'] ? 'bg-green-500' : 'bg-red-500' }}" 
                                     title="{{ format_date($day['date']) }}: {{ $day['attended'] ? 'Attended' : 'Not Attended' }}"
                                     style="min-width: 2px;"></div>
                            @endforeach
                        </div>
                        @else
                        <div class="w-full bg-gray-200 rounded-full h-3 mb-2"></div>
                        @endif
                        
                        <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded mr-1"></div>
                                <span>Attended: {{ $attendedCount }}</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded mr-1"></div>
                                <span>Missed: {{ $missedCount }}</span>
                            </div>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            @if($now->lt($startDate))
                                @php
                                    $daysUntilStart = (int) round($now->diffInDays($startDate));
                                @endphp
                                Plan starts in {{ $daysUntilStart }} {{ $daysUntilStart == 1 ? 'day' : 'days' }}
                            @elseif($now->gt($endDate))
                                Plan completed - {{ $attendedCount }} of {{ $totalDays }} days attended
                            @else
                                {{ $attendedCount }} attended, {{ $missedCount }} missed out of {{ $daysPassed }} days
                            @endif
                        </p>
                    </div>
                    @endif
                    @if($workoutPlan->duration_weeks)
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold">Plan Duration:</span> {{ $workoutPlan->duration_weeks }} {{ $workoutPlan->duration_weeks == 1 ? 'week' : 'weeks' }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Demo Video Section --}}
        @if($workoutPlan->demo_video_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($workoutPlan->demo_video_path))
        @php
            // Use asset() helper - works with storage symlink and .htaccess handles MIME types
            $videoPath = $workoutPlan->demo_video_path;
            $videoUrl = asset('storage/' . $videoPath);
            $videoExtension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
            $mimeType = match($videoExtension) {
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                default => 'video/mp4',
            };
        @endphp
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Demo Video
            </h2>
            <p class="text-sm text-gray-600 mb-4">Watch the demo video to see how to perform the exercises correctly.</p>
            <div class="bg-gray-50 rounded-lg p-4">
                <video 
                    controls 
                    preload="metadata"
                    playsinline
                    class="w-full rounded-lg bg-gray-900"
                    style="max-height: 500px;"
                    onerror="console.error('Video load error:', this.error); this.nextElementSibling.style.display='block';"
                    onloadstart="console.log('Video loading started');"
                    oncanplay="console.log('Video can play');">
                    <source src="{{ $videoUrl }}" type="{{ $mimeType }}">
                    Your browser does not support the video tag.
                    <p class="text-red-600 mt-2" style="display:none;">If you see this message, the video file may be missing or corrupted. Please contact support.</p>
                </video>
                <p class="text-xs text-gray-500 mt-2">Video URL: <a href="{{ $videoUrl }}" target="_blank" class="text-blue-600 hover:underline">{{ $videoUrl }}</a></p>
            </div>
        </div>
        @elseif($workoutPlan->demo_video_path)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <div class="flex items-center text-yellow-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="font-semibold">Demo video file not found. Path: {{ $workoutPlan->demo_video_path }}</span>
            </div>
        </div>
        @endif

        {{-- Exercises List Card --}}
        @if($workoutPlan->exercises && is_array($workoutPlan->exercises) && count($workoutPlan->exercises) > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Exercises ({{ count($workoutPlan->exercises) }})
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($workoutPlan->exercises as $index => $exercise)
                <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <span class="text-green-600 font-semibold text-sm">{{ $index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $exercise }}</h3>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Exercises Added</h3>
                <p class="mt-2 text-sm text-gray-500">Exercises will be added by your trainer.</p>
            </div>
        </div>
        @endif

        {{-- Notes Card --}}
        @if($workoutPlan->notes)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Trainer Notes
            </h2>
            <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ $workoutPlan->notes }}</p>
            </div>
        </div>
        @endif

        {{-- Workout Video Upload Section --}}
        @if($workoutPlan->status === 'active' && $workoutPlan->exercises && is_array($workoutPlan->exercises) && count($workoutPlan->exercises) > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Record Workout Videos
            </h2>
            <p class="text-sm text-gray-600 mb-6">Record a 1-minute video for each exercise and upload for trainer approval.</p>
            
            <div class="space-y-6">
                @foreach($workoutPlan->exercises as $index => $exercise)
                    @php
                        $uploadedVideo = \App\Models\WorkoutVideo::where('workout_plan_id', $workoutPlan->id)
                            ->where('user_id', auth()->id())
                            ->where('exercise_name', $exercise)
                            ->latest()
                            ->first();
                    @endphp
                    
                    <div class="border border-gray-200 rounded-lg p-4" id="exercise-{{ $index }}">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $exercise }}</h3>
                                <p class="text-sm text-gray-500">Exercise {{ $index + 1 }} of {{ count($workoutPlan->exercises) }}</p>
                            </div>
                            @if($uploadedVideo)
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    {{ $uploadedVideo->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $uploadedVideo->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $uploadedVideo->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($uploadedVideo->status) }}
                                </span>
                            @endif
                        </div>
                        
                        @if($uploadedVideo && $uploadedVideo->status === 'approved')
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center text-green-800 mb-3">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold">Video Approved</span>
                                </div>
                                
                                {{-- Video Player --}}
                                @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($uploadedVideo->video_path))
                                @php
                                    // Use asset() helper - works with storage symlink and .htaccess handles MIME types
                                    $videoPath = $uploadedVideo->video_path;
                                    $videoUrl = asset('storage/' . $videoPath);
                                    $videoExtension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
                                    $mimeType = match($videoExtension) {
                                        'mp4' => 'video/mp4',
                                        'webm' => 'video/webm',
                                        'mov' => 'video/quicktime',
                                        'avi' => 'video/x-msvideo',
                                        default => 'video/mp4',
                                    };
                                @endphp
                                <div class="mb-3">
                                    <video 
                                        controls 
                                        preload="metadata"
                                        playsinline
                                        class="w-full max-w-md rounded-lg bg-gray-900"
                                        style="max-height: 400px;"
                                        onerror="console.error('Video load error:', this.error)">
                                        <source src="{{ $videoUrl }}" type="{{ $mimeType }}">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                @else
                                <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                                    <p class="text-sm text-red-600">Video file not found at: {{ $uploadedVideo->video_path }}</p>
                                </div>
                                @endif
                                
                                @if($uploadedVideo->trainer_feedback)
                                    <div class="mt-3 pt-3 border-t border-green-200">
                                        <p class="text-sm font-medium text-green-800 mb-1">Trainer Feedback:</p>
                                        <p class="text-sm text-green-700">{{ $uploadedVideo->trainer_feedback }}</p>
                                    </div>
                                @endif
                                
                                @if($uploadedVideo->reviewed_at)
                                    <p class="text-xs text-green-600 mt-2">
                                        Reviewed on {{ format_date($uploadedVideo->reviewed_at) }}
                                    </p>
                                @endif
                            </div>
                        @elseif($uploadedVideo && $uploadedVideo->status === 'rejected')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center text-red-800 mb-3">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold">Video Rejected</span>
                                </div>
                                
                                {{-- Video Player (for reference) --}}
                                @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($uploadedVideo->video_path))
                                @php
                                    $videoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($uploadedVideo->video_path);
                                    $videoExtension = pathinfo($uploadedVideo->video_path, PATHINFO_EXTENSION);
                                    $mimeType = match(strtolower($videoExtension)) {
                                        'mp4' => 'video/mp4',
                                        'webm' => 'video/webm',
                                        'mov' => 'video/quicktime',
                                        default => 'video/mp4',
                                    };
                                @endphp
                                <div class="mb-3">
                                    <video 
                                        controls 
                                        preload="metadata"
                                        class="w-full max-w-md rounded-lg bg-gray-900"
                                        style="max-height: 400px;"
                                        onerror="console.error('Video load error:', this.error)">
                                        <source src="{{ $videoUrl }}" type="{{ $mimeType }}">
                                        <source src="{{ $videoUrl }}" type="video/mp4">
                                        <source src="{{ $videoUrl }}" type="video/webm">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                @else
                                <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                                    <p class="text-sm text-red-600">Video file not found at: {{ $uploadedVideo->video_path }}</p>
                                </div>
                                @endif
                                
                                @if($uploadedVideo->trainer_feedback)
                                    <div class="mt-3 pt-3 border-t border-red-200">
                                        <p class="text-sm font-medium text-red-800 mb-1">Trainer Feedback:</p>
                                        <p class="text-sm text-red-700">{{ $uploadedVideo->trainer_feedback }}</p>
                                    </div>
                                @endif
                                
                                <p class="text-sm text-red-600 mt-3">Please record and upload a new video.</p>
                            </div>
                        @elseif($uploadedVideo && $uploadedVideo->status === 'pending')
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center text-yellow-800 mb-3">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold">Video Pending Review</span>
                                </div>
                                
                                {{-- Video Player (for preview while pending) --}}
                                @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($uploadedVideo->video_path))
                                @php
                                    $videoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($uploadedVideo->video_path);
                                    $videoExtension = pathinfo($uploadedVideo->video_path, PATHINFO_EXTENSION);
                                    $mimeType = match(strtolower($videoExtension)) {
                                        'mp4' => 'video/mp4',
                                        'webm' => 'video/webm',
                                        'mov' => 'video/quicktime',
                                        default => 'video/mp4',
                                    };
                                @endphp
                                <div class="mb-3">
                                    <video 
                                        controls 
                                        preload="metadata"
                                        class="w-full max-w-md rounded-lg bg-gray-900"
                                        style="max-height: 400px;"
                                        onerror="console.error('Video load error:', this.error)">
                                        <source src="{{ $videoUrl }}" type="{{ $mimeType }}">
                                        <source src="{{ $videoUrl }}" type="video/mp4">
                                        <source src="{{ $videoUrl }}" type="video/webm">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                @else
                                <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                                    <p class="text-sm text-red-600">Video file not found at: {{ $uploadedVideo->video_path }}</p>
                                </div>
                                @endif
                                
                                <p class="text-sm text-yellow-700">Your video is waiting for trainer approval.</p>
                            </div>
                        @endif
                        
                        <div class="video-recorder-container" data-exercise="{{ $exercise }}" data-index="{{ $index }}">
                            {{-- Video Preview --}}
                            <div class="mb-4">
                                <video id="preview-{{ $index }}" class="w-full max-w-md rounded-lg bg-gray-900 hidden" autoplay muted playsinline></video>
                                <div id="recording-status-{{ $index }}" class="hidden text-center py-4">
                                    <div class="inline-flex items-center text-red-600">
                                        <svg class="animate-pulse w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="8"></circle>
                                        </svg>
                                        <span class="font-semibold">Recording... <span id="timer-{{ $index }}">0:00</span></span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Controls --}}
                            <div class="flex flex-wrap gap-3">
                                <button type="button" 
                                        onclick="startRecording({{ $index }}, '{{ $exercise }}')"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Start Recording
                                </button>
                                <button type="button" 
                                        id="stop-btn-{{ $index }}"
                                        onclick="stopRecording({{ $index }})"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium hidden flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path>
                                    </svg>
                                    Stop Recording
                                </button>
                                <button type="button" 
                                        id="upload-btn-{{ $index }}"
                                        onclick="uploadVideo({{ $index }}, '{{ $exercise }}', {{ $workoutPlan->id }})"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium hidden flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Upload Video
                                </button>
                            </div>
                            
                            <div id="upload-status-{{ $index }}" class="mt-4 hidden"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('member.workout-plans') }}" 
               class="flex-1 sm:flex-none px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-center">
                Back to Plans
            </a>
        </div>
    </div>
</div>
@endsection

