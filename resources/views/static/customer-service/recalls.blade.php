@extends('static.customer-service._layout', ['breadcrumb' => 'recalls'])

@section('title', 'Product Recalls | LarpShop')

@section('page-content')
    <section class="cs-hero mb-5 text-center">
        <span class="badge bg-danger bg-opacity-25 text-danger-emphasis px-3 py-2 rounded-pill fw-semibold mb-3 border border-danger border-opacity-25">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Safety Notices
        </span>
        <h1 class="display-5 fw-bold mb-3">Product Recalls &amp; Safety Notices</h1>
        <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
            Your safety on the battlefield comes first. Here is how we handle unsafe products.
        </p>
    </section>

    <section class="mb-5">
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <div><strong>No active recalls.</strong> There are currently no recalled products on LarpShop.</div>
        </div>
    </section>

    <section class="mb-5">
        <h3 class="fw-bold mb-3">How Our Recall Process Works</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="cs-card p-4 h-100 shadow-sm">
                    <div class="cs-icon bg-primary bg-opacity-10 text-primary mb-3"><i class="bi bi-search"></i></div>
                    <h5 class="fw-bold">1. Investigation</h5>
                    <p class="text-muted small mb-0">Reports are reviewed by our team, and the vendor is asked to provide material and construction details.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cs-card p-4 h-100 shadow-sm">
                    <div class="cs-icon bg-warning bg-opacity-10 text-warning mb-3"><i class="bi bi-slash-circle"></i></div>
                    <h5 class="fw-bold">2. Removal</h5>
                    <p class="text-muted small mb-0">Confirmed unsafe products are removed from sale and the vendor may be suspended.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cs-card p-4 h-100 shadow-sm">
                    <div class="cs-icon bg-success bg-opacity-10 text-success mb-3"><i class="bi bi-cash-coin"></i></div>
                    <h5 class="fw-bold">3. Remedy</h5>
                    <p class="text-muted small mb-0">Affected customers are notified and offered a full refund or replacement.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 rounded-4 shadow-sm bg-white p-4 p-lg-5">
        <h4 class="fw-bold mb-2">Report an Unsafe Product</h4>
        <p class="text-secondary" style="line-height: 1.7;">
            If a weapon or piece of gear has an exposed core, broken tip, or other hazard, stop using it and contact us with your order number and photos.
        </p>
        <div>
            <a href="/contact" class="btn btn-danger rounded-pill px-4 fw-semibold">
                <i class="bi bi-flag me-1"></i> Report a Problem
            </a>
        </div>
    </section>
@endsection
