import '../bootstrap';
import { initYajraDataTable } from './datatables';
import { initAllRichTextEditors } from './rich-text-editor';
import { initConfirmDialogs } from './confirm-dialog';

// Import jQuery and DataTables locally
import $ from 'jquery';
import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5';

// Import DataTables CSS
import 'datatables.net-dt/css/dataTables.dataTables.min.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.min.css';
import '../../css/admin/datatables.css';

// Make jQuery globally available
window.$ = window.jQuery = $;

// Initialize DataTables enhancement automatically
initYajraDataTable();

// Helper: detect user's timezone and send to backend (reuse same endpoint as frontend)
function detectAndSetAdminTimezone() {
    try {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const now = new Date();
        const utcOffset = -now.getTimezoneOffset(); // minutes

        fetch('/timezone/set', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name=\"csrf-token\"]')
                    ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                timezone: timezone,
                offset: utcOffset,
            }),
        }).catch((error) => {
            console.log('Admin timezone detection failed:', error);
        });
    } catch (error) {
        console.log('Admin timezone detection error:', error);
    }
}

// Admin specific JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all rich text editors on the page
    initAllRichTextEditors();
    initConfirmDialogs();

    // Detect admin/trainer timezone so dates use system/browser timezone
    detectAndSetAdminTimezone();
});

