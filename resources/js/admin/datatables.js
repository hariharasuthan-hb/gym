/**
 * Yajra DataTables Enhancement
 * Enhances DataTables with professional styling and fixes loading indicator
 */

/**
 * Enhance a specific DataTable instance
 */
function enhanceDataTable(tableId) {
    const $ = window.$ || window.jQuery;

    if (!$ || !$.fn.DataTable) {
        return false;
    }

    if (!$(tableId).length) {
        return false;
    }

    setTimeout(function() {
        try {
            const table = $(tableId).DataTable();
            if (table) {
                // Hide processing indicator after table loads
                table.on('processing', function(e, settings, processing) {
                    if (!processing) {
                        const processingEl = $('#' + settings.sTableId + '_processing');
                        if (processingEl.length) {
                            processingEl.fadeOut();
                        }
                    }
                });

                // Enhance with smooth fade-in animation on draw
                table.on('draw', function() {
                    const api = this.api();
                    $(api.table().body()).find('tr').css('opacity', '0');
                    $(api.table().body()).find('tr').each(function(index) {
                        $(this).delay(index * 50).animate({opacity: 1}, 200);
                    });
                });
            }
        } catch (e) {
            // DataTable not ready
        }
    }, 300);
}

/**
 * Auto-enhance all DataTables
 */
function autoEnhanceDataTables() {
    const $ = window.$ || window.jQuery;
    
    if (!$ || !$.fn.DataTable) {
        setTimeout(autoEnhanceDataTables, 100);
        return;
    }

    document.querySelectorAll('table.dataTable').forEach(function(table) {
        if (table.id) {
            enhanceDataTable('#' + table.id);
        }
    });
}

/**
 * Initialize DataTables enhancement
 */
export function initYajraDataTable() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoEnhanceDataTables);
    } else {
        autoEnhanceDataTables();
    }
}


