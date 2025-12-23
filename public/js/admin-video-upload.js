/**
 * Optimized Video Upload for Admin Workout Plan Demo Videos
 * Uses reusable VideoUploadUtils class
 */

(function() {
    'use strict';
    
    // Initialize video upload utility
    const videoUploader = new VideoUploadUtils({
        chunkSize: 5 * 1024 * 1024, // 5MB chunks
        compressionThreshold: 20 * 1024 * 1024, // 20MB
        // For admin demo videos we want a simple, reliable flow.
        // Disable chunked uploads for normal sizes so a 40–50MB file
        // is sent as a single request, avoiding partial chunk assembly.
        // Our PHP/nginx limits are 200M, so this is safe.
        chunkedThreshold: 500 * 1024 * 1024, // effectively "no chunking" under 200M
    });

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const demoVideoInput = document.getElementById('demo_video');
        if (!demoVideoInput) return;

        const form = demoVideoInput.closest('form');
        if (!form) return;

        // Create upload status container
        const statusContainer = document.createElement('div');
        statusContainer.id = 'demo-video-upload-status';
        statusContainer.className = 'mt-4 hidden';
        demoVideoInput.parentElement.appendChild(statusContainer);

        // Handle file selection
        demoVideoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file using utility
            const validation = videoUploader.validateFile(file, 100 * 1024 * 1024);
            if (!validation.valid) {
                showError(validation.error);
                return;
            }

            // Show upload option
            showUploadOption(file);
        });

        // Show upload option with preview
        function showUploadOption(file) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            statusContainer.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Selected: ${file.name}</p>
                            <p class="text-xs text-blue-700">Size: ${fileSizeMB} MB</p>
                        </div>
                        <button type="button" id="start-upload-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Upload Video
                        </button>
                    </div>
                    <div id="upload-progress-container" class="hidden">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-blue-800" id="upload-status-text">Preparing upload...</span>
                            <span class="text-sm font-semibold text-blue-800" id="upload-progress-text">0%</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div id="upload-progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');

            // Handle upload button click
            const uploadBtn = document.getElementById('start-upload-btn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', () => uploadVideo(file));
            }
        }

        // Upload video with optimization
        async function uploadVideo(file) {

            const uploadBtn = document.getElementById('start-upload-btn');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const progressText = document.getElementById('upload-progress-text');
            const statusText = document.getElementById('upload-status-text');

            if (uploadBtn) uploadBtn.disabled = true;
            if (progressContainer) progressContainer.classList.remove('hidden');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const uploadUrl = '/admin/workout-plans/upload-demo-video';
                const chunkedUploadUrl = '/admin/workout-plans/upload-demo-video-chunk';


                // Use reusable upload utility
                const response = await videoUploader.upload(
                    file,
                    uploadUrl,
                    chunkedUploadUrl,
                    csrfToken,
                    {
                        onProgress: (percent, loaded, total) => {
                            progressBar.style.width = percent + '%';
                            progressText.textContent = Math.round(percent) + '%';
                        },
                        onCompressStart: () => {
                            statusText.textContent = 'Compressing video...';
                        },
                        onCompressEnd: () => {
                            statusText.textContent = 'Uploading video...';
                        },
                        additionalData: {
                            _videoFieldName: 'demo_video' // Admin expects 'demo_video' field name
                        }
                    }
                );

                // Validate response has required data
                if (!response.video_path) {
                    throw new Error('Server did not return video_path in response. Check server logs for conversion errors.');
                }

                // Create hidden input with video path
                let videoPathInput = document.getElementById('demo_video_path');
                if (!videoPathInput) {
                    videoPathInput = document.createElement('input');
                    videoPathInput.type = 'hidden';
                    videoPathInput.id = 'demo_video_path';
                    videoPathInput.name = 'demo_video_path';
                    form.appendChild(videoPathInput);
                }
                videoPathInput.value = response.video_path;

                // Disable file input since we've uploaded via AJAX
                demoVideoInput.disabled = true;

                // Re-enable form submission (upload is finished; conversion continues in queue)
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }

                // Show simple success message (no long-running processing state)
                statusContainer.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center text-green-800">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold">
                                Video uploaded. It is now processing on the server; you can submit the form.
                            </span>
                        </div>
                    </div>
                `;
            } catch (error) {
                console.error('Upload failed with error:', error);
                console.error('Error stack:', error.stack);
                showError('Upload failed: ' + error.message);
            } finally {
                if (uploadBtn) uploadBtn.disabled = false;
            }
        }


        // Show processing status and poll for conversion completion
        function showProcessingStatus(videoPath) {
            // Disable form submission
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent || submitBtn.value;
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    `;
                }
            }

            statusContainer.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center text-yellow-800">
                        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-semibold">Processing video conversion... Please wait.</span>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');

            // Poll for conversion completion
            pollConversionStatus(videoPath, submitBtn);
        }

        // Poll server to check if conversion is complete
        async function pollConversionStatus(videoPath, submitBtn) {
            const maxAttempts = 120; // 2 minutes max (120 * 1 second)
            let attempts = 0;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            const checkStatus = async () => {
                attempts++;

                try {
                    const response = await fetch('/admin/workout-plans/check-video-conversion?video_path=' + encodeURIComponent(videoPath), {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (data.is_complete) {
                        // Conversion complete!
                        statusContainer.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center text-green-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="font-semibold">Video uploaded successfully! You can now submit the form.</span>
                                </div>
                            </div>
                        `;

                        // Re-enable form submission
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtn.tagName === 'BUTTON') {
                                submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
                            } else {
                                submitBtn.value = submitBtn.dataset.originalText || 'Submit';
                            }
                        }
                    } else if (attempts >= maxAttempts) {
                        // Timeout - show warning but allow submission
                        statusContainer.innerHTML = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center text-yellow-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span class="font-semibold">Video conversion is taking longer than expected. You can submit the form, but the video may still be processing.</span>
                                </div>
                            </div>
                        `;

                        // Re-enable form submission
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtn.tagName === 'BUTTON') {
                                submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
                            } else {
                                submitBtn.value = submitBtn.dataset.originalText || 'Submit';
                            }
                        }
                    } else {
                        // Still processing, check again in 1 second
                        setTimeout(checkStatus, 1000);
                    }
                } catch (error) {
                    console.error('Error checking conversion status:', error);
                    // On error, allow submission after a few attempts
                    if (attempts >= 10) {
                        statusContainer.innerHTML = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center text-yellow-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span class="font-semibold">Unable to verify conversion status. You can submit the form.</span>
                                </div>
                            </div>
                        `;

                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtn.tagName === 'BUTTON') {
                                submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
                            } else {
                                submitBtn.value = submitBtn.dataset.originalText || 'Submit';
                            }
                        }
                    } else {
                        setTimeout(checkStatus, 1000);
                    }
                }
            };

            // Start polling
            checkStatus();
        }

        // Show error message
        function showError(message) {
            statusContainer.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center text-red-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold">${message}</span>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');
        }
    });
})();

