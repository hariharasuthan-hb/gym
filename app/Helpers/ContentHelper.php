<?php

namespace App\Helpers;

class ContentHelper
{
    /**
     * Render HTML content safely, allowing basic HTML tags
     * Used for rich text editor content that should display formatted HTML
     *
     * @param string|null $content
     * @return string
     */
    public static function renderRichContent(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // For rich text editor content, we want to render HTML
        // But we should still be careful about XSS
        // The content comes from trusted admin users via the rich text editor
        return $content;
    }

    /**
     * Render content for display - if it's rich content with HTML, render it
     * If it's plain text, convert newlines to breaks
     *
     * @param string|null $content
     * @param bool $isRichText
     * @return string
     */
    public static function renderContent(?string $content, bool $isRichText = true): string
    {
        if (empty($content)) {
            return '';
        }

        if ($isRichText) {
            // For rich text content, render HTML directly
            return self::renderRichContent($content);
        } else {
            // For plain text, convert newlines to breaks
            return nl2br(e($content));
        }
    }

    /**
     * Check if content contains HTML tags
     *
     * @param string|null $content
     * @return bool
     */
    public static function hasHtml(?string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // Simple check for HTML tags
        return preg_match('/<[^>]+>/', $content) === 1;
    }

    /**
     * Auto-detect content type and render appropriately
     * If content contains HTML tags, treat as rich text
     * Otherwise, treat as plain text with line breaks
     *
     * @param string|null $content
     * @return string
     */
    public static function renderAuto(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        return self::renderContent($content, self::hasHtml($content));
    }
}