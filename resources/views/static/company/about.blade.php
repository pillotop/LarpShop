@extends('layouts.app')

@section('title', 'About Us | LarpShop')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/top_header.css',
        'resources/css/components/navigation_bar.css',
    ])
    <style>
        .hero-banner {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 60%, #415a77 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(2, 200, 245, 0.15) 0%, rgba(2, 200, 245, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .stat-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        .value-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e9ecef;
            height: 100%;
            transition: all 0.2s ease-in-out;
        }
        .value-card:hover {
            border-color: #02c8f5;
            box-shadow: 0 8px 24px rgba(2, 200, 245, 0.1);
        }
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
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
            {{ Breadcrumbs::render('about') }}
        </div>

        {{-- Hero Section --}}
        <section class="hero-banner mb-5 text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-semibold mb-3">
                        <i class="bi bi-shield-shaded me-1"></i> The Premier Roleplay Marketplace
                    </span>
                    <h1 class="display-4 fw-bold mb-3">Crafted for Quests, Built for Heroes</h1>
                    <p class="lead mb-4 text-light-emphasis" style="max-width: 650px;">
                        LarpShop connects passionate live-action roleplayers with independent armorers, leatherworkers, and prop crafters from across the realms.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-compass me-1"></i> Explore Armory
                        </a>
                        <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-semibold">
                            <i class="bi bi-chat-dots me-1"></i> Contact Guild
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 backdrop-blur">
                        <i class="bi bi-award-fill text-warning display-1 mb-2"></i>
                        <h4 class="fw-bold text-white mb-1">Safety Certified Gear</h4>
                        <p class="small text-light mb-0">Every foam weapon and leather piece strictly vetted to international LARP battle standards.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Key Numbers / Milestones --}}
        <section class="mb-5">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-card p-4 text-center bg-white shadow-sm">
                        <div class="text-primary fw-bold display-6 mb-1">50K+</div>
                        <div class="text-muted fw-semibold small text-uppercase">Adventurers Served</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card p-4 text-center bg-white shadow-sm">
                        <div class="text-primary fw-bold display-6 mb-1">1,200+</div>
                        <div class="text-muted fw-semibold small text-uppercase">Vetted Artisans</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card p-4 text-center bg-white shadow-sm">
                        <div class="text-primary fw-bold display-6 mb-1">35+</div>
                        <div class="text-muted fw-semibold small text-uppercase">Countries Reached</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card p-4 text-center bg-white shadow-sm">
                        <div class="text-primary fw-bold display-6 mb-1">99.8%</div>
                        <div class="text-muted fw-semibold small text-uppercase">Satisfaction Rate</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Mission & Story --}}
        <section class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-lg-5 bg-white">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <span class="text-primary fw-bold text-uppercase small tracking-wide">Our Origin</span>
                        <h2 class="fw-bold display-6 mb-3">From Weekend Campaigns to a Global Guild</h2>
                        <p class="text-secondary" style="line-height: 1.8;">
                            LarpShop was born out of a real problem faced by tabletop and live-action gamers: finding high-quality, combat-safe foam swords, authentic medieval tunics, and handcrafted leather bracers shouldn't require scouring disconnected forums or waiting months for unreliable vendors.
                        </p>
                        <p class="text-secondary" style="line-height: 1.8;">
                            In 2024, our founders gathered a cadre of indie prop masters and reenactment enthusiasts to build an open, transparent marketplace. Today, we empower independent armorers to reach global adventurers while guaranteeing safe materials, verified merchant reputations, and seamless delivery.
                        </p>
                        <div class="mt-4 pt-2 border-top d-flex gap-4">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Zero-Compromise</h5>
                                <small class="text-muted">Rigid battle safety testing</small>
                            </div>
                            <div class="border-start ps-4">
                                <h5 class="fw-bold mb-0 text-dark">Fair Trade</h5>
                                <small class="text-muted">Direct compensation to smiths</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-light p-4 rounded-4 border">
                            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-compass-fill text-primary"></i> Our North Star Mission
                            </h4>
                            <p class="text-muted mb-4" style="line-height: 1.7;">
                                To make immersive storytelling and safe roleplay accessible to anyone, anywhere—from first-time tavern bards to legendary guild commanders—by championing artisanal makers and battle-tested quality.
                            </p>
                            <div class="p-3 bg-white rounded-3 border-start border-4 border-info shadow-sm">
                                <em class="text-secondary small d-block">
                                    "When costume and foam meet craftsmanship and honor, ordinary weekends become unforgettable legends."
                                </em>
                                <span class="fw-bold small text-dark mt-2 d-block">— The LarpShop Council</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Core Values --}}
        <section class="mb-5">
            <div class="text-center mb-4">
                <span class="text-primary fw-bold text-uppercase small">What We Stand For</span>
                <h2 class="fw-bold">Our Guiding Tenets</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="value-card p-4 shadow-sm">
                        <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Uncompromising Safety</h5>
                        <p class="text-muted small mb-0">
                            Reinforced fiberglass cores, tear-resistant closed-cell foams, and latex finishes checked against strict LARP rulebooks.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="value-card p-4 shadow-sm">
                        <div class="icon-circle bg-info bg-opacity-10 text-info mb-3">
                            <i class="bi bi-hammer"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Master Craftsmanship</h5>
                        <p class="text-muted small mb-0">
                            We champion independent leather crafters, blacksmiths, and seamstresses who pour their heart into every stitch and rivet.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="value-card p-4 shadow-sm">
                        <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Inclusive Community</h5>
                        <p class="text-muted small mb-0">
                            Roleplay is for everyone. We welcome all genres—from historical medieval reenactment to dark fantasy, post-apocalyptic, and sci-fi.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="value-card p-4 shadow-sm">
                        <div class="icon-circle bg-success bg-opacity-10 text-success mb-3">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Eco-Conscious Durability</h5>
                        <p class="text-muted small mb-0">
                            Gear built to last countless campaigns, reducing single-use plastic props with resilient, repairable materials.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Call To Action --}}
        <section class="card border-0 rounded-4 shadow-sm text-center p-5 bg-dark text-white">
            <h2 class="fw-bold mb-2">Join the Guild Today</h2>
            <p class="text-white-50 mb-4 mx-auto" style="max-width: 600px;">
                Whether you're gearing up for your very first weekend battle or an artisan eager to showcase your forge, your adventure begins here.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-bag-check me-1"></i> Browse Marketplace
                </a>
                <a href="{{ route('contact') }}" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-envelope me-1"></i> Get in Touch
                </a>
            </div>
        </section>
    </main>

    <x-side-panel/>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
