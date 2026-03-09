@extends('emails.layout')

@section('title', 'New Lead Notification')

@section('header-title', 'New Contact Form Submission')
@section('header-subtitle', 'You have received a new lead from your website')

@section('content')
    <p style="margin-top: 0;">Hello,</p>
    
    <p>A new contact form submission has been received from your website. Please find the details below:</p>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ $name }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">
                <a href="mailto:{{ $email }}">{{ $email }}</a>
            </span>
        </div>
        
        @if($phone)
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value">
                <a href="tel:{{ $phone }}">{{ $phone }}</a>
            </span>
        </div>
        @endif
    </div>
    
    @if($leadMessage)
    <div class="message-box">
        <strong style="display: block; margin-bottom: 10px; color: #495057;">Message:</strong>
        <p>{{ $leadMessage }}</p>
    </div>
    @endif
    
    <p style="margin-top: 30px;">This lead has been automatically added to your leads management system. You can view and manage it from the admin panel.</p>
    
    <p style="margin-top: 20px;">
        <strong>Next Steps:</strong><br>
        • Review the lead details in your admin panel<br>
        • Assign the lead to a team member if needed<br>
        • Follow up with the customer promptly
    </p>
@endsection

@section('footer')
    <p style="margin: 0;">This is an automated notification from {{ config('app.name', 'Gym Management System') }}</p>
    <p style="margin: 5px 0 0 0;">Please do not reply to this email. To respond to the customer, use the email address provided above.</p>
@endsection
