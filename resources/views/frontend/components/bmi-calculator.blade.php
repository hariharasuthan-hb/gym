{{-- BMI Calculator Section --}}
@php
    $cmsBmiCalculator = $cmsBmiCalculator ?? null;

    // Use CMS content if available, otherwise use defaults
    $bmiTitle = $cmsBmiCalculator && $cmsBmiCalculator->title
        ? $cmsBmiCalculator->title
        : 'BMI Calculator';

    $bmiDescription = $cmsBmiCalculator && ($cmsBmiCalculator->description ?? $cmsBmiCalculator->content)
        ? ($cmsBmiCalculator->description ?? $cmsBmiCalculator->content)
        : 'Check your Body Mass Index instantly';

    // Check for background video/image (priority: video > image)
    $bmiBackgroundVideo = null;
    $bmiBackgroundImage = null;

    if ($cmsBmiCalculator) {
        if ($cmsBmiCalculator->background_video) {
            $bmiBackgroundVideo = \Illuminate\Support\Facades\Storage::url($cmsBmiCalculator->background_video);
        } elseif ($cmsBmiCalculator->background_image) {
            $bmiBackgroundImage = \Illuminate\Support\Facades\Storage::url($cmsBmiCalculator->background_image);
        }
    }

    $hasBackground = $bmiBackgroundVideo || $bmiBackgroundImage;
    $bgStyle = $bmiBackgroundImage && !$bmiBackgroundVideo
        ? "background-image: url('{$bmiBackgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;"
        : '';
@endphp

<section id="bmi-calculator" class="py-12 sm:py-16 md:py-20 lg:py-24 {{ $hasBackground ? 'relative min-h-[400px] sm:min-h-[500px] md:min-h-[600px] overflow-hidden' : 'bg-gray-50' }}" style="{{ $bgStyle }}">
    @if($bmiBackgroundVideo)
        <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="{{ $bmiBackgroundVideo }}" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @elseif($bmiBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif

    <div class="container mx-auto px-3 sm:px-4 md:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 break-words leading-tight px-2 sm:px-0 {{ $hasBackground ? 'text-white' : 'text-gray-900' }}" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                {!! render_content($bmiTitle) !!}
            </h2>
            <p class="{{ $hasBackground ? 'text-white' : 'text-gray-600' }} max-w-2xl mx-auto text-sm sm:text-base md:text-lg px-2 sm:px-0 break-words leading-relaxed" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                {!! render_content($bmiDescription) !!}
            </p>
        </div>

        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                <form id="bmi-form" class="space-y-6">
                    {{-- Height Input --}}
                    <div>
                        <label for="height" class="block text-sm font-medium text-gray-700 mb-2">
                            Height (cm)
                        </label>
                        <input type="number" 
                               id="height" 
                               name="height" 
                               min="50" 
                               max="300" 
                               step="0.1"
                               placeholder="Enter height in cm"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                    </div>

                    {{-- Weight Input --}}
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                            Weight (kg)
                        </label>
                        <input type="number" 
                               id="weight" 
                               name="weight" 
                               min="10" 
                               max="500" 
                               step="0.1"
                               placeholder="Enter weight in kg"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                    </div>

                    {{-- Calculate Button --}}
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-lg">
                        Calculate BMI
                    </button>
                </form>

                {{-- Results Section with Speedometer Gauge --}}
                <div id="bmi-results" class="mt-6 hidden">
                    <div class="border-t border-gray-200 pt-6">
                        {{-- BMI Gauge Speedometer --}}
                        <div class="mb-6">
                            <div class="relative mx-auto" style="width: 100%; max-width: 500px; aspect-ratio: 2/1;">
                                <svg id="bmi-gauge" viewBox="0 0 500 280" class="w-full h-full" style="overflow: visible;">
                                    <defs>
                                        <linearGradient id="underweightGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#87CEEB;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#B0E0E6;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="normalGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#4CAF50;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#66BB6A;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="overweightGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#FFC107;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#FFD54F;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="obeseGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#FF9800;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#FFB74D;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="extremelyObeseGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#F44336;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#E57373;stop-opacity:1" />
                                        </linearGradient>
                                    </defs>

                                    {{-- Gauge Arc Segments (calculated for 180-degree semi-circle) --}}
                                    {{-- Center: (250, 250), Radius: 180 --}}
                                    
                                    {{-- Underweight: 0-18.5 --}}
                                    <path id="gauge-segment-1" d="M 70 250 A 180 180 0 0 1 190 80" 
                                          fill="none" stroke="url(#underweightGrad)" stroke-width="38" 
                                          stroke-linecap="round" opacity="0.85"/>
                                    
                                    {{-- Normal: 18.5-24.9 --}}
                                    <path id="gauge-segment-2" d="M 190 80 A 180 180 0 0 1 248 50" 
                                          fill="none" stroke="url(#normalGrad)" stroke-width="38" 
                                          stroke-linecap="round" opacity="0.85"/>
                                    
                                    {{-- Overweight: 25-29.9 --}}
                                    <path id="gauge-segment-3" d="M 248 50 A 180 180 0 0 1 310 80" 
                                          fill="none" stroke="url(#overweightGrad)" stroke-width="38" 
                                          stroke-linecap="round" opacity="0.85"/>
                                    
                                    {{-- Obese: 30-34.9 --}}
                                    <path id="gauge-segment-4" d="M 310 80 A 180 180 0 0 1 370 120" 
                                          fill="none" stroke="url(#obeseGrad)" stroke-width="38" 
                                          stroke-linecap="round" opacity="0.85"/>
                                    
                                    {{-- Extremely Obese: 35+ --}}
                                    <path id="gauge-segment-5" d="M 370 120 A 180 180 0 0 1 430 250" 
                                          fill="none" stroke="url(#extremelyObeseGrad)" stroke-width="38" 
                                          stroke-linecap="round" opacity="0.85"/>

                                    {{-- Gauge Center Point --}}
                                    <circle cx="250" cy="250" r="10" fill="#333" />

                                    {{-- Needle (will be rotated via JavaScript) --}}
                                    <g id="needle" transform="rotate(-90 250 250)">
                                        <line x1="250" y1="250" x2="250" y2="80" 
                                              stroke="#000" stroke-width="4" stroke-linecap="round"/>
                                        <circle cx="250" cy="250" r="8" fill="#C0C0C0" stroke="#333" stroke-width="2"/>
                                        <circle cx="250" cy="250" r="5" fill="#333"/>
                                    </g>

                                    {{-- Category Labels --}}
                                    <text x="70" y="240" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">UNDERWEIGHT</text>
                                    <text x="70" y="255" font-size="10" fill="#666" text-anchor="middle">&lt;18.5</text>
                                    
                                    <text x="190" y="65" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">NORMAL</text>
                                    <text x="190" y="78" font-size="10" fill="#666" text-anchor="middle">18.5-24.9</text>
                                    
                                    <text x="310" y="65" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">OVERWEIGHT</text>
                                    <text x="310" y="78" font-size="10" fill="#666" text-anchor="middle">25-29.9</text>
                                    
                                    <text x="370" y="105" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">OBESE</text>
                                    <text x="370" y="118" font-size="10" fill="#666" text-anchor="middle">30-34.9</text>
                                    
                                    <text x="430" y="240" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">EXTREMELY</text>
                                    <text x="430" y="253" font-size="11" fill="#333" font-weight="bold" text-anchor="middle">OBESE</text>
                                    <text x="430" y="268" font-size="10" fill="#666" text-anchor="middle">&gt;35</text>
                                </svg>
                            </div>

                            {{-- BMI Value Display --}}
                            <div class="text-center mt-4">
                                <div class="bg-blue-600 text-white py-2 px-6 rounded-lg inline-block mb-2">
                                    <p class="text-sm font-semibold uppercase tracking-wide">BODY MASS INDEX</p>
                                </div>
                                <div id="bmi-value-display" class="bg-yellow-400 text-gray-900 py-3 px-8 rounded-lg inline-block mb-3">
                                    <p id="bmi-value" class="text-5xl font-bold">0</p>
                                </div>
                                <p id="bmi-category" class="text-2xl font-bold mb-4"></p>
                            </div>

                            {{-- Legend --}}
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="flex flex-wrap justify-center gap-2 text-xs">
                                    <div class="flex items-center gap-1">
                                        <div class="w-4 h-4 rounded" style="background: linear-gradient(90deg, #87CEEB, #B0E0E6);"></div>
                                        <span class="text-gray-600">&lt;18.5 <strong>UNDERWEIGHT</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-4 h-4 rounded" style="background: linear-gradient(90deg, #4CAF50, #66BB6A);"></div>
                                        <span class="text-gray-600">18.5-24.9 <strong>NORMAL</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-4 h-4 rounded" style="background: linear-gradient(90deg, #FFC107, #FFD54F);"></div>
                                        <span class="text-gray-600">25-29.9 <strong>OVERWEIGHT</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-4 h-4 rounded" style="background: linear-gradient(90deg, #FF9800, #FFB74D);"></div>
                                        <span class="text-gray-600">30-34.9 <strong>OBESE</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-4 h-4 rounded" style="background: linear-gradient(90deg, #F44336, #E57373);"></div>
                                        <span class="text-gray-600">&gt;35 <strong>EXTREMELY OBESE</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #needle {
        transform-origin: 250px 250px;
        transition: transform 2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    #bmi-value-display {
        animation: pulse 0.5s ease-in-out;
    }

    #bmi-gauge {
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bmi-form');
    const resultsDiv = document.getElementById('bmi-results');
    const bmiValue = document.getElementById('bmi-value');
    const bmiCategory = document.getElementById('bmi-category');
    const needle = document.getElementById('needle');

    // Gauge configuration
    const centerX = 250;
    const centerY = 250;
    const radius = 180;
    const minBMI = 0;
    const maxBMI = 50;
    const startAngle = -90; // Left side
    const endAngle = 90;    // Right side
    const angleRange = endAngle - startAngle; // 180 degrees

    // BMI category ranges
    const bmiRanges = [
        { min: 0, max: 18.5, color: 'url(#underweightGrad)', id: 'gauge-segment-1' },
        { min: 18.5, max: 24.9, color: 'url(#normalGrad)', id: 'gauge-segment-2' },
        { min: 25, max: 29.9, color: 'url(#overweightGrad)', id: 'gauge-segment-3' },
        { min: 30, max: 34.9, color: 'url(#obeseGrad)', id: 'gauge-segment-4' },
        { min: 35, max: 50, color: 'url(#extremelyObeseGrad)', id: 'gauge-segment-5' }
    ];

    // Calculate point on circle
    function getPointOnCircle(angle, centerX, centerY, radius) {
        const rad = (angle * Math.PI) / 180;
        return {
            x: centerX + radius * Math.cos(rad),
            y: centerY + radius * Math.sin(rad)
        };
    }

    // Calculate angle from BMI value
    function bmiToAngle(bmi) {
        const clampedBMI = Math.max(minBMI, Math.min(maxBMI, bmi));
        const normalizedBMI = (clampedBMI - minBMI) / (maxBMI - minBMI);
        return startAngle + (normalizedBMI * angleRange);
    }

    // Update gauge segments with accurate calculations (optional enhancement)
    // The segments are already defined in HTML, but we can enhance them if needed

    function calculateNeedleAngle(bmi) {
        return bmiToAngle(bmi);
    }

    function getBMICategory(bmi) {
        if (bmi < 18.5) {
            return {
                name: 'UNDERWEIGHT',
                color: 'text-blue-600',
                bgColor: 'bg-blue-100'
            };
        } else if (bmi >= 18.5 && bmi < 25) {
            return {
                name: 'NORMAL',
                color: 'text-green-600',
                bgColor: 'bg-green-100'
            };
        } else if (bmi >= 25 && bmi < 30) {
            return {
                name: 'OVERWEIGHT',
                color: 'text-yellow-600',
                bgColor: 'bg-yellow-100'
            };
        } else if (bmi >= 30 && bmi < 35) {
            return {
                name: 'OBESE',
                color: 'text-orange-600',
                bgColor: 'bg-orange-100'
            };
        } else {
            return {
                name: 'EXTREMELY OBESE',
                color: 'text-red-600',
                bgColor: 'bg-red-100'
            };
        }
    }

    function animateNeedle(targetAngle) {
        // Reset needle to start position
        needle.setAttribute('transform', `rotate(${startAngle} ${centerX} ${centerY})`);
        
        // Force reflow
        needle.offsetHeight;
        
        // Animate to target angle with smooth transition
        setTimeout(() => {
            needle.setAttribute('transform', `rotate(${targetAngle} ${centerX} ${centerY})`);
        }, 50);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const height = parseFloat(document.getElementById('height').value);
        const weight = parseFloat(document.getElementById('weight').value);

        if (!height || !weight || height <= 0 || weight <= 0) {
            alert('Please enter valid height and weight values.');
            return;
        }

        // Calculate BMI: weight / (height / 100)^2
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);
        const roundedBmi = parseFloat(bmi.toFixed(1));

        // Get category
        const category = getBMICategory(roundedBmi);

        // Calculate needle angle
        const needleAngle = calculateNeedleAngle(roundedBmi);

        // Show results
        resultsDiv.classList.remove('hidden');
        
        // Update BMI value with animation
        bmiValue.textContent = roundedBmi;
        bmiCategory.textContent = category.name;
        bmiCategory.className = 'text-2xl font-bold ' + category.color;

        // Animate needle
        animateNeedle(needleAngle);
        
        // Scroll to results
        setTimeout(() => {
            resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    });
});
</script>

