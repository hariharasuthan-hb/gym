@extends('emails.layout')

@section('title', 'Thank You for Contacting Us')

@section('header-title', 'Thank You!')
@section('header-subtitle', 'We have received your message')

@section('content')
    <p style="margin-top: 0;">Hello {{ $name }},</p>
    
    <p>Thank you for reaching out to us! We have successfully received your contact form submission.</p>
    
    <div class="info-section" style="background-color: #e7f5e7; border-left-color: #28a745;">
        <p style="margin: 0; color: #155724;">
            <strong>✓ Your message has been received</strong><br>
            Our team will review your inquiry and get back to you as soon as possible, typically within 24-48 hours.
        </p>
    </div>
    
    <p style="margin-top: 30px;">We appreciate your interest in our services and look forward to assisting you.</p>
    
    <p style="margin-top: 20px;">
        <strong>What happens next?</strong><br>
        • Your inquiry has been logged in our system<br>
        • Our team will review your message<br>
        • We'll respond to you at the email address you provided<br>
        • If your inquiry is urgent, please feel free to call us directly
    </p>
    
    @php
        $siteSettings = \App\Models\SiteSetting::getSettings();
    @endphp
    
    @if($siteSettings->contact_mobile || $siteSettings->contact_email)
    <div class="info-section" style="margin-top: 30px;">
        <p style="margin: 0 0 10px 0; font-weight: 600; color: #495057;">Need immediate assistance?</p>
        @if($siteSettings->contact_mobile)
        <p style="margin: 5px 0;">
            <strong>Phone:</strong> 
            <a href="tel:{{ $siteSettings->contact_mobile }}">{{ $siteSettings->contact_mobile }}</a>
        </p>
        @endif
        @if($siteSettings->contact_email)
        <p style="margin: 5px 0;">
            <strong>Email:</strong> 
            <a href="mailto:{{ $siteSettings->contact_email }}">{{ $siteSettings->contact_email }}</a>
        </p>
        @endif
    </div>
    @endif
    
    <p style="margin-top: 30px;">Best regards,<br>
    <strong>{{ config('app.name', 'Gym Management Team') }}</strong></p>
@endsection

@section('footer')
    <p style="margin: 0;">This is an automated confirmation email from {{ config('app.name', 'Gym Management System') }}</p>
    <p style="margin: 5px 0 0 0;">If you did not submit this contact form, please ignore this email.</p>
@endsection
