<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactRequest;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Send contact email to the configured site contact email
        $siteSettings = SiteSetting::getSettings();
        $toEmail = $siteSettings->contact_email ?? config('mail.from.address');

        if ($toEmail) {
            $subject = 'New contact message from landing page';

            $bodyLines = [
                "You have received a new contact message from the landing page.",
                "",
                "Name: {$validated['name']}",
                "Email: {$validated['email']}",
            ];

            if (!empty($validated['phone'])) {
                $bodyLines[] = "Phone: {$validated['phone']}";
            }

            $bodyLines[] = "";
            $bodyLines[] = "Message:";
            $bodyLines[] = $validated['message'];

            $body = implode(PHP_EOL, $bodyLines);

            Mail::raw($body, function ($message) use ($toEmail, $subject, $validated) {
                $message->to($toEmail)
                    ->subject($subject);

                // Set reply-to so admin can answer directly
                if (!empty($validated['email'])) {
                    $message->replyTo($validated['email'], $validated['name'] ?? null);
                }
            });
        }

        return redirect()->route('frontend.home')
            ->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
