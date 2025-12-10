/**
 * Rich Text Editor Initialization
 *
 * Simple rich text editor using contentEditable
 * Provides basic formatting without external dependencies
 */

/**
 * Initialize a rich text editor instance
 *
 * @param {string} editorId - The ID of the textarea element
 * @param {object} options - Configuration options
 */
export function initRichTextEditor(editorId, options = {}) {
    const textarea = document.getElementById(editorId);
    if (!textarea) {
        console.warn(`Rich text editor element with ID '${editorId}' not found`);
        return;
    }

    const height = options.height || 400;
    const toolbar = options.toolbar || 'basic';

    // Create container for the editor
    const container = document.createElement('div');
    container.className = 'simple-rich-editor-container';
    container.style.cssText = `
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        min-height: ${height}px;
        background: white;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    `;

    // Create toolbar
    const toolbarElement = document.createElement('div');
    toolbarElement.className = 'simple-rich-editor-toolbar';
    toolbarElement.style.cssText = `
        border-bottom: 1px solid #e5e7eb;
        padding: 0.5rem 0.75rem;
        background: #f9fafb;
        border-radius: 0.375rem 0.375rem 0 0;
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
        align-items: center;
    `;

    // Create editable content area
    const editable = document.createElement('div');
    editable.className = 'simple-rich-editor-content';
    editable.contentEditable = true;
    editable.style.cssText = `
        padding: 0.75rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 14px;
        line-height: 1.6;
        min-height: ${height - 60}px;
        outline: none;
        white-space: pre-wrap;
        word-wrap: break-word;
        color: #374151;
    `;

    // Set initial content
    editable.innerHTML = textarea.value || '';

    // Toolbar buttons based on configuration
    const buttons = [];

    if (toolbar === 'full' || toolbar === 'basic') {
        buttons.push(
            { command: 'bold', icon: 'B', title: 'Bold' },
            { command: 'italic', icon: 'I', title: 'Italic' },
            { command: 'underline', icon: 'U', title: 'Underline' }
        );
    }

    if (toolbar === 'full') {
        buttons.push(
            { command: 'insertUnorderedList', icon: '•', title: 'Bullet List' },
            { command: 'insertOrderedList', icon: '1.', title: 'Numbered List' },
            { command: 'justifyLeft', icon: '⬅', title: 'Align Left' },
            { command: 'justifyCenter', icon: '⬌', title: 'Align Center' },
            { command: 'justifyRight', icon: '➡', title: 'Align Right' }
        );
    }

    // Create toolbar buttons
    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.type = 'button';
        button.innerHTML = btn.icon;
        button.title = btn.title;
        button.style.cssText = `
            padding: 0.25rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            background: white;
            color: #374151;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            min-width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        `;

        button.addEventListener('mouseover', () => {
            button.style.background = '#f3f4f6';
            button.style.borderColor = '#9ca3af';
        });

        button.addEventListener('mouseout', () => {
            button.style.background = 'white';
            button.style.borderColor = '#d1d5db';
        });

        button.addEventListener('click', (e) => {
            e.preventDefault();
            document.execCommand(btn.command, false, null);
            editable.focus();
        });

        toolbarElement.appendChild(button);
    });

    // Sync content back to textarea
    const syncContent = () => {
        textarea.value = editable.innerHTML;
    };

    editable.addEventListener('input', syncContent);
    editable.addEventListener('blur', syncContent);

    // Handle form submission
    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', syncContent);
    }

    // Replace textarea with editor
    container.appendChild(toolbarElement);
    container.appendChild(editable);
    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(container, textarea);

    // Add focus/blur styling
    editable.addEventListener('focus', () => {
        container.style.borderColor = '#3b82f6';
        container.style.boxShadow = '0 0 0 3px rgb(59 130 246 / 0.1)';
    });

    editable.addEventListener('blur', () => {
        container.style.borderColor = '#d1d5db';
        container.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
    });

    // Focus the editor
    setTimeout(() => editable.focus(), 100);
}

/**
 * Initialize all rich text editors on the page
 */
export function initAllRichTextEditors() {
    document.querySelectorAll('.rich-text-editor').forEach(function(textarea) {
        const editorId = textarea.id;
        if (!editorId) {
            console.warn('Rich text editor textarea missing ID');
            return;
        }

        const toolbar = textarea.dataset.toolbar || 'full';
        const height = parseInt(textarea.dataset.height) || 400;

        initRichTextEditor(editorId, {
            toolbar: toolbar,
            height: height
        });
    });
}

/**
 * Destroy all rich text editors
 * Useful for cleanup or when navigating away
 */
export function destroyAllRichTextEditors() {
    // For contentEditable editors, we don't need special cleanup
    // The DOM elements will be removed when the page changes
}

// Make functions globally available
window.initRichTextEditor = initRichTextEditor;
window.initAllRichTextEditors = initAllRichTextEditors;
window.destroyAllRichTextEditors = destroyAllRichTextEditors;

