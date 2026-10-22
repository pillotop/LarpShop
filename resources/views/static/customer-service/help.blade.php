@extends('static.customer-service._layout', ['breadcrumb' => 'help'])

@section('title', 'Help Centre | LarpShop')

@section('page-content')
    <section class="cs-hero mb-5 text-center">
        <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
            <i class="bi bi-life-preserver me-1"></i> Help Centre
        </span>
        <h1 class="display-5 fw-bold mb-3">How Can We Help You Today?</h1>
        <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
            Find answers about orders, shipping, returns, weapon safety, and your account.
        </p>
    </section>

    <section class="mb-5">
        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <a href="#orders" class="text-decoration-none">
                    <div class="cs-card p-4 text-center h-100 shadow-sm">
                        <div class="cs-icon bg-primary bg-opacity-10 text-primary mb-2"><i class="bi bi-box-seam"></i></div>
                        <h6 class="fw-bold text-dark mb-0">Orders &amp; Shipping</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="#returns" class="text-decoration-none">
                    <div class="cs-card p-4 text-center h-100 shadow-sm">
                        <div class="cs-icon bg-success bg-opacity-10 text-success mb-2"><i class="bi bi-arrow-return-left"></i></div>
                        <h6 class="fw-bold text-dark mb-0">Returns &amp; Refunds</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="#safety" class="text-decoration-none">
                    <div class="cs-card p-4 text-center h-100 shadow-sm">
                        <div class="cs-icon bg-danger bg-opacity-10 text-danger mb-2"><i class="bi bi-shield-check"></i></div>
                        <h6 class="fw-bold text-dark mb-0">Weapon Safety</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="#account" class="text-decoration-none">
                    <div class="cs-card p-4 text-center h-100 shadow-sm">
                        <div class="cs-icon bg-warning bg-opacity-10 text-warning mb-2"><i class="bi bi-person-gear"></i></div>
                        <h6 class="fw-bold text-dark mb-0">Account &amp; Vendors</h6>
                    </div>
                </a>
            </div>
        </div>
    </section>

    @php
        $faqGroups = [
            'orders' => ['title' => 'Orders & Shipping', 'items' => [
                ['How long does delivery take?', 'Ready-to-ship items dispatch within 2-4 business days. Custom-crafted gear takes 2-4 weeks depending on the vendor.'],
                ['Can I track my order?', 'Yes. Once your order ships, the status is updated on your order page.'],
                ['Can I change my address after ordering?', 'Contact us as soon as possible. Changes are only possible before the vendor ships your order.'],
            ]],
            'returns' => ['title' => 'Returns & Refunds', 'items' => [
                ['What is the return policy?', 'Unused items in original condition can be returned within 30 days. Custom-made items are only refundable if defective.'],
                ['How long do refunds take?', 'Refunds are processed within 5-7 business days after the return is received.'],
            ]],
            'safety' => ['title' => 'Weapon Safety', 'items' => [
                ['Are foam weapons safe for combat?', 'Weapons must meet LARP safety guidelines: flexible cores, padded tips, and closed-cell foam. Always follow your event\'s weapon check rules.'],
                ['What if a product fails inspection?', 'Contact us with photos. If the item was listed as combat-safe, we will arrange a refund or replacement.'],
            ]],
            'account' => ['title' => 'Account & Vendors', 'items' => [
                ['I forgot my password. What now?', 'Use the "Forgot password" link on the login page to receive a reset email.'],
                ['How do I become a vendor?', 'Apply from your Profile page with your store name and description. An admin will review your application.'],
            ]],
        ];
    @endphp

    @foreach ($faqGroups as $groupId => $group)
        <section id="{{ $groupId }}" class="mb-4">
            <h3 class="fw-bold mb-3">{{ $group['title'] }}</h3>
            <div class="accordion bg-white rounded-4 shadow-sm" id="acc-{{ $groupId }}">
                @foreach ($group['items'] as $i => $item)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}-{{ $i }}">
                                {{ $item[0] }}
                            </button>
                        </h2>
                        <div id="{{ $groupId }}-{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#acc-{{ $groupId }}">
                            <div class="accordion-body text-secondary small" style="line-height: 1.7;">{{ $item[1] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="card border-0 rounded-4 shadow-sm text-center p-5 bg-dark text-white mt-5">
        <h3 class="fw-bold mb-2">Still need help?</h3>
        <p class="text-white-50 mb-3">Our support team replies within 1-2 business days.</p>
        <div>
            <a href="/contact" class="btn btn-primary rounded-pill px-4 fw-semibold">
                <i class="bi bi-envelope me-1"></i> Contact Support
            </a>
        </div>
    </section>
@endsection
