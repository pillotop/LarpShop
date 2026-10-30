@extends('layouts.app')

@section('title', 'Press & Media Room | LarpShop')

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
        .press-hero {
            background: linear-gradient(135deg, #091e3a 0%, #173753 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 3.5rem 2rem;
        }
        .press-release-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            padding: 1.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .press-release-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        .asset-card {
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            background: #ffffff;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.2s ease;
        }
        .asset-card:hover {
            border-color: #02c8f5;
        }
        .color-swatch {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
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
            {{ Breadcrumbs::render('press') }}
        </div>

        {{-- Hero Header --}}
        <section class="press-hero mb-5 text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
                        <i class="bi bi-broadcast me-1"></i> Press & Media Center
                    </span>
                    <h1 class="display-5 fw-bold mb-3">Official News, Brand Assets & Media Kit</h1>
                    <p class="lead mb-4 text-light-emphasis" style="max-width: 650px;">
                        Stay updated with company announcements, partnership releases, executive commentary, and download verified brand assets for coverage.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="#press-releases" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-newspaper me-1"></i> Latest Announcements
                        </a>
                        <a href="#brand-kit" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                            <i class="bi bi-palette me-1"></i> Download Brand Kit
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10">
                        <i class="bi bi-chat-quote-fill text-info display-4 mb-2"></i>
                        <h5 class="fw-bold text-white mb-2">Media Hotline</h5>
                        <p class="small text-light mb-3">Journalists and event organizers can reach our press desk directly for quotes and interviews.</p>
                        <a href="mailto:press@larpshop.com" class="btn btn-sm btn-info text-dark rounded-pill px-3 fw-bold">
                            press@larpshop.com
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Fast Facts --}}
        <section class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                <div class="row g-3 text-center">
                    <div class="col-md-3 border-end border-light-subtle">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Founded</div>
                        <div class="fw-bold fs-4 text-dark">2024</div>
                    </div>
                    <div class="col-md-3 border-end border-light-subtle">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Headquarters</div>
                        <div class="fw-bold fs-4 text-dark">Austin, TX</div>
                    </div>
                    <div class="col-md-3 border-end border-light-subtle">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Active Smiths</div>
                        <div class="fw-bold fs-4 text-primary">1,200+ Artisans</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Market Category</div>
                        <div class="fw-bold fs-4 text-dark">Immersive Commerce</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Press Releases --}}
        <section id="press-releases" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="text-primary fw-bold text-uppercase small">Recent Dispatches</span>
                    <h3 class="fw-bold mb-0">Official Press Releases</h3>
                </div>
                <span class="text-muted small">Updated October 2026</span>
            </div>

            <div class="d-flex flex-column gap-3">
                <div class="press-release-card shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">Partnership</span>
                        <small class="text-muted">October 15, 2026</small>
                    </div>
                    <h4 class="fw-bold mb-2">LarpShop Announces Official Collaboration with European Roleplay Federation</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.7;">
                        LarpShop has entered a landmark multi-year sponsorship and safety alignment with major European live-action gaming bodies to standardize weapon test compliance and streamline gear delivery to international participants.
                    </p>
                    <div class="d-flex align-items-center gap-3 text-muted small">
                        <span><i class="bi bi-geo-alt me-1"></i> Austin, TX & Cologne, Germany</span>
                        <span>•</span>
                        <span>Full text available for accredited press</span>
                    </div>
                </div>

                <div class="press-release-card shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Growth & Milestones</span>
                        <small class="text-muted">September 02, 2026</small>
                    </div>
                    <h4 class="fw-bold mb-2">LarpShop Exceeds 10,000 Custom Crafted Weapons Shipped with 99.8% Safety Record</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.7;">
                        Highlighting the strength of its artisan onboarding and safety inspection protocols, LarpShop confirmed reaching a record milestone of 10,000 certified foam combat swords and shields delivered across 35 countries.
                    </p>
                    <div class="d-flex align-items-center gap-3 text-muted small">
                        <span><i class="bi bi-geo-alt me-1"></i> Austin, TX</span>
                        <span>•</span>
                        <span>Includes Q3 platform transaction data</span>
                    </div>
                </div>

                <div class="press-release-card shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">Product Innovation</span>
                        <small class="text-muted">July 20, 2026</small>
                    </div>
                    <h4 class="fw-bold mb-2">Launch of Artisan Storefront Portal: Empowering Indie Leatherworkers & Armorers</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.7;">
                        The newly debuted seller tooling enables independent crafters to easily open customized web storefronts, manage bespoke commissioning queues, and connect directly with roleplay guilds.
                    </p>
                    <div class="d-flex align-items-center gap-3 text-muted small">
                        <span><i class="bi bi-geo-alt me-1"></i> Austin, TX</span>
                        <span>•</span>
                        <span>Features interviews with 5 premier smiths</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Brand Assets & Media Kit --}}
        <section id="brand-kit" class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-lg-5 bg-white">
                <span class="text-primary fw-bold text-uppercase small">Media Kit</span>
                <h3 class="fw-bold mb-3">Official Brand Assets & Guidelines</h3>
                <p class="text-muted mb-4" style="max-width: 700px;">
                    When referencing LarpShop in articles, podcasts, or event programs, please use our approved logos, color specifications, and brand typography.
                </p>

                <div class="row g-4">
                    {{-- Logos --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="asset-card shadow-sm">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-image text-primary"></i> Logo Vectors & Raster
                            </h5>
                            <p class="text-muted small mb-3">
                                Available in SVG and high-resolution PNG format with transparent backgrounds for light and dark layouts.
                            </p>
                            <div class="p-3 bg-light rounded-3 text-center mb-3">
                                <img src="/images/larp_shop.jpg" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;" alt="LarpShop Logo Preview">
                            </div>
                            <button class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold" type="button" disabled title="Asset Pack Preview">
                                <i class="bi bi-download me-1"></i> Download Logo Pack (.zip)
                            </button>
                        </div>
                    </div>

                    {{-- Colors --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="asset-card shadow-sm">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-palette-fill text-info"></i> Color Palette
                            </h5>
                            <p class="text-muted small mb-3">
                                Our primary brand colors evoke medieval steel, enchanted azure, and twilight citadel stone.
                            </p>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="color-swatch" style="background-color: #02c8f5;"></div>
                                    <div>
                                        <div class="fw-bold small text-dark">Mythic Cyan (#02c8f5)</div>
                                        <small class="text-muted">Primary Brand Accent</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="color-swatch" style="background-color: #0d1b2a;"></div>
                                    <div>
                                        <div class="fw-bold small text-dark">Citadel Navy (#0d1b2a)</div>
                                        <small class="text-muted">Backgrounds & Hero Titles</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Executive Bio & PR Contact --}}
                    <div class="col-md-6 col-lg-4">
                        <div class="asset-card shadow-sm">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge text-warning"></i> Press Inquiries
                            </h5>
                            <p class="text-muted small mb-3">
                                For urgent quotes, executive interviews, or hi-res photo requests for published stories:
                            </p>
                            <div class="p-3 bg-light rounded-3 mb-3">
                                <div class="fw-semibold text-dark small mb-1">Director of Communications</div>
                                <div class="text-muted small">Elena Vance</div>
                                <a href="mailto:press@larpshop.com" class="small fw-semibold text-primary text-decoration-none d-block mt-1">
                                    press@larpshop.com
                                </a>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-clock me-1"></i> Standard response SLA: Under 24 hours.
                            </div>
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
