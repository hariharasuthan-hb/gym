import '../bootstrap';

// Frontend specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Timezone detection
    detectAndSetUserTimezone();
});

/**
 * Detect user's timezone and send to server
 */
function detectAndSetUserTimezone() {
    try {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // Get UTC offset in minutes
        const now = new Date();
        const utcOffset = -now.getTimezoneOffset(); // getTimezoneOffset returns minutes

        // Send timezone to server if user is logged in
        if (window.Laravel && window.Laravel.user) {
            fetch('/timezone/set', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    timezone: timezone,
                    offset: utcOffset
                })
            }).catch(error => {
                console.log('Timezone detection failed:', error);
            });
        } else {
            // Store in session for guest users
            sessionStorage.setItem('detected_timezone', timezone);
        }
    } catch (error) {
        console.log('Timezone detection error:', error);
    }
}

