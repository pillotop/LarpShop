@extends('static.customer-service._layout', ['breadcrumb' => 'community'])

@section('title', 'Community Forum | LarpShop')

@section('page-content')
    <section class="cs-hero mb-5 text-center">
        <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-2 rounded-pill fw-semibold mb-3 border border-warning border-opacity-25">
            <i class="bi bi-people-fill me-1"></i> Community Forum
        </span>
        <h1 class="display-5 fw-bold mb-3">Gather at the Tavern</h1>
        <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
            Share builds, swap crafting tips, and find your next campaign with fellow roleplayers.
        </p>
    </section>

    <section class="mb-5">
        <div class="alert alert-info rounded-4 d-flex align-items-center gap-2" role="status">
            <i class="bi bi-info-circle-fill"></i>
            <div>The interactive forum is coming soon. Below is a preview of the planned boards.</div>
        </div>

        @php
            $boards = [
                ['bi-hammer', 'primary', 'Crafting & DIY', 'Foam smithing, leatherwork, and prop-making techniques.'],
                ['bi-flag-fill', 'danger', 'Events & Campaigns', 'Find events, organise groups, and share battle reports.'],
                ['bi-person-vcard', 'success', 'Costuming & Garb', 'Show off outfits and discuss historical accuracy.'],
                ['bi-shop', 'warning', 'Vendor Corner', 'Announcements, tips, and feedback for artisans.'],
            ];
        @endphp

        <div class="row g-4">
            @foreach ($boards as $b)
                <div class="col-md-6">
                    <div class="cs-card p-4 shadow-sm h-100 d-flex gap-3">
                        <div class="cs-icon bg-{{ $b[1] }} bg-opacity-10 text-{{ $b[1] }} flex-shrink-0">
                            <i class="bi {{ $b[0] }}"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">{{ $b[2] }}</h5>
                            <p class="text-muted small mb-0">{{ $b[3] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card border-0 rounded-4 shadow-sm bg-white p-4 p-lg-5">
        <h3 class="fw-bold mb-3"><i class="bi bi-journal-check text-primary me-2"></i>Community Guidelines</h3>
        <ul class="text-secondary mb-0" style="line-height: 1.9;">
            <li>Be respectful. No harassment, hate speech, or personal attacks.</li>
            <li>Keep safety first. Do not promote unsafe weapons or practices.</li>
            <li>No spam or unsolicited advertising outside the Vendor Corner.</li>
            <li>Protect privacy. Never share other people's personal information.</li>
            <li>Stay in character only where the board allows it.</li>
        </ul>
    </section>
@endsection
