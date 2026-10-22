@extends('layouts.app')

@section('title', 'Contact Us | LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/top_header.css">
    <link rel="stylesheet" href="/css/components/navigation_bar.css">
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #102a43 0%, #243b53 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 3.5rem 2rem;
        }
        .contact-info-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .contact-info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        .contact-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
    </style>
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories"
            :cart="$cart"
        />
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs --}}
        <div class="mb-4">
            {{ Breadcrumbs::render('contact') }}
        </div>

        {{-- Hero Header --}}
        <section class="contact-hero mb-5 text-center">
            <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
                <i class="bi bi-headset me-1"></i> Customer & Guild Support
            </span>
            <h1 class="display-5 fw-bold mb-3">Send a Dispatch to the LarpShop Guild</h1>
            <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
                Have questions about weapon specs, custom leather armor, an ongoing order, or merchant onboarding? We are at your service.
            </p>
        </section>

        {{-- Contact Info Cards --}}
        <section class="mb-5">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="contact-info-card p-4 shadow-sm">
                        <div class="contact-icon-box bg-primary bg-opacity-10 text-primary mb-3">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Customer Support</h5>
                        <p class="text-muted small mb-3">Order queries, tracking, and product guidance.</p>
                        <a href="mailto:support@larpshop.com" class="fw-semibold text-primary text-decoration-none">
                            support@larpshop.com
                        </a>
                        <div class="text-muted small mt-1">Mon – Fri: 8am – 6pm EST</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-info-card p-4 shadow-sm">
                        <div class="contact-icon-box bg-success bg-opacity-10 text-success mb-3">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Artisan & Vendors</h5>
                        <p class="text-muted small mb-3">Merchant onboarding and catalog inquiries.</p>
                        <a href="mailto:vendors@larpshop.com" class="fw-semibold text-success text-decoration-none">
                            vendors@larpshop.com
                        </a>
                        <div class="text-muted small mt-1">Dedicated Smith Support</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-info-card p-4 shadow-sm">
                        <div class="contact-icon-box bg-info bg-opacity-10 text-info mb-3">
                            <i class="bi bi-newspaper"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Press & Media</h5>
                        <p class="text-muted small mb-3">Event sponsorships, reviews, and press kit.</p>
                        <a href="mailto:press@larpshop.com" class="fw-semibold text-info text-decoration-none">
                            press@larpshop.com
                        </a>
                        <div class="text-muted small mt-1">Response within 24 hours</div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-info-card p-4 shadow-sm">
                        <div class="contact-icon-box bg-warning bg-opacity-10 text-warning mb-3">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Headquarters</h5>
                        <p class="text-muted small mb-3">LarpShop Global Operations Citadel.</p>
                        <span class="fw-semibold text-dark d-block">
                            1042 Mythic Citadel Way
                        </span>
                        <div class="text-muted small mt-1">Suite 400, Austin, TX 78701</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Contact Form and Office Details --}}
        <section class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="row g-0">
                    <div class="col-lg-7 p-4 p-md-5">
                        <h3 class="fw-bold mb-2">Send Us an Inquiry</h3>
                        <p class="text-muted mb-4">Fill out the message dispatch form below and our team will get back to your guild promptly.</p>

                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center rounded-3 mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3 mb-4">
                                <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Please correct the errors below:
                                </div>
                                <ul class="mb-0 ps-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('contact.submit') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold small text-muted">Your Name or Alias <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        placeholder="e.g. Sir Cedric of Riverdale"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold small text-muted">Email Address <span class="text-danger">*</span></label>
                                    <input
                                        type="email"
                                        class="form-control rounded-3 @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="cedric@example.com"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label fw-semibold small text-muted">Department <span class="text-danger">*</span></label>
                                    <select class="form-select rounded-3 @error('department') is-invalid @enderror" id="department" name="department" required>
                                        <option value="support" {{ old('department') == 'support' ? 'selected' : '' }}>Customer Support & Weapon Specs</option>
                                        <option value="orders" {{ old('department') == 'orders' ? 'selected' : '' }}>Order Status & Shipping</option>
                                        <option value="vendor" {{ old('department') == 'vendor' ? 'selected' : '' }}>Artisan / Vendor Partnership</option>
                                        <option value="partnership" {{ old('department') == 'partnership' ? 'selected' : '' }}>Event Sponsorship & Conventions</option>
                                        <option value="general" {{ old('department') == 'general' ? 'selected' : '' }}>General Feedback</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="subject" class="form-label fw-semibold small text-muted">Subject <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 @error('subject') is-invalid @enderror"
                                        id="subject"
                                        name="subject"
                                        value="{{ old('subject') }}"
                                        placeholder="Brief summary of your inquiry"
                                        required
                                    >
                                </div>

                                <div class="col-12">
                                    <label for="message" class="form-label fw-semibold small text-muted">Detailed Message <span class="text-danger">*</span></label>
                                    <textarea
                                        class="form-control rounded-3 @error('message') is-invalid @enderror"
                                        id="message"
                                        name="message"
                                        rows="5"
                                        placeholder="Describe your inquiry, order details, or prop specifications..."
                                        required
                                    >{{ old('message') }}</textarea>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-send-fill"></i>
                                        <span>Dispatch Message</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-5 bg-light p-4 p-md-5 border-start border-light-subtle d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history text-primary"></i> Operating Hours & SLA
                            </h4>
                            <p class="text-muted small" style="line-height: 1.6;">
                                Our support team is active across all standard US and European time zones during major roleplay campaign seasons.
                            </p>
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex justify-content-between py-2 border-bottom text-muted small">
                                    <span class="fw-semibold text-dark">Monday – Friday</span>
                                    <span>8:00 AM – 6:00 PM EST</span>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom text-muted small">
                                    <span class="fw-semibold text-dark">Saturday (Convention Support)</span>
                                    <span>10:00 AM – 4:00 PM EST</span>
                                </li>
                                <li class="d-flex justify-content-between py-2 text-muted small">
                                    <span class="fw-semibold text-dark">Sunday</span>
                                    <span class="badge bg-secondary-subtle text-secondary">Tavern Rest</span>
                                </li>
                            </ul>

                            <div class="p-3 bg-white rounded-3 border mb-3">
                                <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-lock text-success"></i> Safe Communication
                                </div>
                                <div class="small text-muted">
                                    We never ask for your passwords or private payment credentials via email or chat.
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top">
                            <span class="small text-muted">Need fast answers? Check our <a href="{{ route('about') }}" class="text-primary text-decoration-none">About Us</a> page or browse the <a href="{{ route('blog') }}" class="text-primary text-decoration-none">Chronicle Blog</a>.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Frequently Asked Questions --}}
        <section class="mb-5">
            <div class="text-center mb-4">
                <span class="text-primary fw-bold text-uppercase small">Need Fast Answers?</span>
                <h3 class="fw-bold">Frequently Asked Questions</h3>
            </div>
            <div class="accordion accordion-flush bg-white rounded-4 shadow-sm border p-3" id="faqAccordion">
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faqHeadingOne">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="false" aria-controls="faqCollapseOne">
                            Are all weapons sold on LarpShop safe for combat?
                        </button>
                    </h2>
                    <div id="faqCollapseOne" class="accordion-collapse collapse" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary small" style="line-height: 1.7;">
                            Yes. All foam and latex weapons listed on LarpShop must comply with standard live-action roleplay safety guidelines. They feature flexible fiberglass cores, puncture-resistant tip enforcement, and closed-cell foam shock absorption. Always check your local event's specific weapon check rules before battle.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header" id="faqHeadingTwo">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                            How do I become a registered vendor or artisan on LarpShop?
                        </button>
                    </h2>
                    <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary small" style="line-height: 1.7;">
                            Any registered user can apply to become a vendor right from their Profile page. Submit your store name and craft description, and once reviewed by our admin council, you can immediately begin listing your armor, props, and costumes.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="faqHeadingThree">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                            What is the typical shipping timeline for custom-crafted gear?
                        </button>
                    </h2>
                    <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary small" style="line-height: 1.7;">
                            Ready-to-ship props and standard tunic sizes generally dispatch within 2-4 business days. Custom-tooled leather cuirasses or bespoke painted latex weapons take between 2 to 4 weeks depending on the artisan's forge schedule.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <x-side-panel/>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
