@extends('static.customer-service._layout', ['breadcrumb' => 'privacy'])

@section('title', 'Privacy Policy | LarpShop')

@section('page-content')
    <section class="cs-hero mb-5 text-center">
        <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
            <i class="bi bi-shield-lock me-1"></i> Privacy Policy
        </span>
        <h1 class="display-5 fw-bold mb-3">Your Privacy Matters to Us</h1>
        <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
            This policy explains what data LarpShop collects, how we use it, and the choices you have.
        </p>
        <p class="small text-white-50 mt-3 mb-0">Last updated: October 2026</p>
    </section>

    @php
        $sections = [
            ['Information We Collect', 'We collect account details (username, email), order and shipping information, vendor store details, and product reviews you submit. We also store basic technical data such as session cookies needed to keep you signed in and to maintain your cart.'],
            ['How We Use Your Information', 'We use your data to process orders, operate your account, show your cart, send password-reset emails, prevent fraud, and improve the marketplace. We do not sell your personal information.'],
            ['Sharing With Vendors', 'When you place an order, the relevant vendor receives only the information needed to fulfil it, such as your name and shipping address.'],
            ['Cookies', 'We use essential cookies for authentication, security (CSRF protection), and cart persistence. We do not use third-party advertising cookies.'],
            ['Data Security', 'Passwords are stored hashed, and forms are protected against cross-site request forgery. No system is perfectly secure, so please use a unique password.'],
            ['Data Retention', 'We keep account and order data while your account is active and as required for legal and accounting purposes.'],
            ['Your Rights', 'You can view and update your profile information at any time from your Profile page, and you can request deletion of your account by contacting us.'],
            ['Changes to This Policy', 'We may update this policy from time to time. Significant changes will be announced on this page.'],
        ];
    @endphp

    <section class="card border-0 rounded-4 shadow-sm bg-white p-4 p-lg-5 mb-5">
        @foreach ($sections as $i => $s)
            <div class="{{ $loop->last ? '' : 'mb-4 pb-4 border-bottom' }}">
                <h4 class="fw-bold mb-2">{{ $i + 1 }}. {{ $s[0] }}</h4>
                <p class="text-secondary mb-0" style="line-height: 1.8;">{{ $s[1] }}</p>
            </div>
        @endforeach
    </section>

    <section class="cs-card p-4 text-center shadow-sm">
        <h5 class="fw-bold mb-1">Questions about privacy?</h5>
        <p class="text-muted small mb-0">
            Email <a href="mailto:privacy@larpshop.com" class="text-decoration-none">privacy@larpshop.com</a>
        </p>
    </section>
@endsection
