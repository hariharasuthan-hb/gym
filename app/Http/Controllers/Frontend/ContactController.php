<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactRequest;
use App\Mail\ContactFormSuccessMail;
use App\Mail\LeadNotificationMail;
use App\Models\Lead;
use App\Models\SiteSetting;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\LeadAssignmentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly LeadAssignmentService $leadAssignmentService
    ) {
    }

    /**
     * Handle contact form submission.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Create lead record
        $leadData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'status' => Lead::STATUS_NEW,
            'source' => Lead::SOURCE_WEBSITE,
        ];

        // Automatically assign lead to a trainer if available
        $assignedTrainerId = $this->leadAssignmentService->assignLeadAutomatically();
        if ($assignedTrainerId) {
            $leadData['assigned_to'] = $assignedTrainerId;
        }

        $this->leadRepository->create($leadData);

        // Send emails (admin notification and customer confirmation)
        // Wrap in try-catch so lead creation succeeds even if email fails
        try {
            $siteSettings = SiteSetting::getSettings();
            $toEmail = $siteSettings->contact_email ?? config('mail.from.address');

            // Send notification email to admin
            if ($toEmail) {
                $adminMail = new LeadNotificationMail(
                    $validated['name'],
                    $validated['email'],
                    $validated['phone'] ?? null,
                    $validated['message']
                );

                Mail::to($toEmail)->send($adminMail);
            }

            // Send success/confirmation email to customer
            $customerMail = new ContactFormSuccessMail($validated['name']);
            Mail::to($validated['email'])
                ->send($customerMail);
        } catch (\Exception $e) {
            // Log the email error but don't fail the request
            // The lead has already been created, which is the most important part
            \Log::error('Failed to send contact form email notification', [
                'error' => $e->getMessage(),
                'lead_email' => $validated['email'],
            ]);
        }

        return redirect()->route('frontend.home')
            ->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
