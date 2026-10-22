@extends('layouts.app')

@section('title', 'Investor Relations | LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/top_header.css">
    <link rel="stylesheet" href="/css/components/navigation_bar.css">
    <style>
        .investor-hero {
            background: linear-gradient(135deg, #0b132b 0%, #1c2541 60%, #3a506b 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 4rem 2rem;
            position: relative;
        }
        .metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        }
        .doc-card {
            border: 1px solid #e9ecef;
            border-radius: 0.75rem;
            padding: 1.25rem;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .doc-card:hover {
            border-color: #02c8f5;
            background-color: #f8fafc;
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
            {{ Breadcrumbs::render('investors') }}
        </div>

        {{-- Hero Banner --}}
        <section class="investor-hero mb-5 text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
                        <i class="bi bi-graph-up-arrow me-1"></i> Investor Relations
                    </span>
                    <h1 class="display-4 fw-bold mb-3">Powering the Global Renaissance of Roleplay Commerce</h1>
                    <p class="lead mb-4 text-light-emphasis" style="max-width: 680px;">
                        LarpShop operates the premier vertically-integrated marketplace connecting millions of live-action gamers and historical reenactors with verified prop armorers and costume designers worldwide.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="#financials" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Key Metrics
                        </a>
                        <a href="mailto:ir@larpshop.com" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                            <i class="bi bi-envelope me-1"></i> Contact IR Team
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10">
                        <div class="text-info fw-bold display-5 mb-1">$4.8B</div>
                        <h6 class="text-white fw-bold mb-2">Global LARP & Tabletop TAM</h6>
                        <p class="small text-light mb-0">Projected 14.2% CAGR through 2030 driven by immersive entertainment and experiential events.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Growth Metrics / Financial Highlights --}}
        <section id="financials" class="mb-5">
            <div class="text-center mb-4">
                <span class="text-primary fw-bold text-uppercase small">Marketplace Performance</span>
                <h2 class="fw-bold">Platform Highlights & Growth Trajectory</h2>
            </div>
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="metric-card p-4 text-center shadow-sm h-100">
                        <div class="text-primary fw-bold display-5 mb-1">+142%</div>
                        <h6 class="fw-bold text-dark mb-1">YoY GMV Growth</h6>
                        <p class="text-muted small mb-0">Gross Merchandise Volume generated by certified blacksmiths and artisans.</p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-card p-4 text-center shadow-sm h-100">
                        <div class="text-success fw-bold display-5 mb-1">78%</div>
                        <h6 class="fw-bold text-dark mb-1">Repeat Customer Rate</h6>
                        <p class="text-muted small mb-0">Adventurers returning for seasonal campaigns and battle upgrades.</p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-card p-4 text-center shadow-sm h-100">
                        <div class="text-info fw-bold display-5 mb-1">1,200+</div>
                        <h6 class="fw-bold text-dark mb-1">Independent Artisans</h6>
                        <p class="text-muted small mb-0">Active storefronts across North America, Europe, and Asia-Pacific.</p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-card p-4 text-center shadow-sm h-100">
                        <div class="text-warning fw-bold display-5 mb-1">94%</div>
                        <h6 class="fw-bold text-dark mb-1">Artisan Retention</h6>
                        <p class="text-muted small mb-0">Sustained high merchant satisfaction through low take rates and seller tooling.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Strategic Investment Pillars --}}
        <section class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-lg-5 bg-white">
                <span class="text-primary fw-bold text-uppercase small">Our Competitive Moats</span>
                <h3 class="fw-bold mb-4">Why LarpShop Wins in Immersive Commerce</h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-4 h-100 border">
                            <div class="fs-2 text-primary mb-2">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Proprietary Safety Verification</h5>
                            <p class="text-muted small mb-0" style="line-height: 1.6;">
                                We pioneered a multi-tier physical safety review process for latex and foam weapons, removing buyer hesitation and ensuring compliance with sanctioned tournament rules.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-4 h-100 border">
                            <div class="fs-2 text-success mb-2">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5 class="fw-bold mb-2">High-LTV Community Flywheel</h5>
                            <p class="text-muted small mb-0" style="line-height: 1.6;">
                                LARP participants invest heavily in character progression across years of play. Our integrated review and store systems foster high brand loyalty and recurring gear purchases.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-4 h-100 border">
                            <div class="fs-2 text-info mb-2">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Scalable Multi-Vendor Engine</h5>
                            <p class="text-muted small mb-0" style="line-height: 1.6;">
                                Built with modern Laravel and Docker infrastructure, our multi-tenant vendor architecture enables seamless storefront management, inventory tracking, and order fulfillment.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Corporate Governance & Documents --}}
        <section class="mb-5">
            <div class="row g-4">
                <div class="col-lg-7">
                    <h3 class="fw-bold mb-3">Investor Materials & Filings</h3>
                    <p class="text-muted mb-4">Access our latest company overviews, sustainability reports, and quarterly presentations.</p>
                    <div class="d-flex flex-column gap-3">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-3 text-danger">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">LarpShop FY2025 Annual Strategic Report</div>
                                    <small class="text-muted">PDF • 4.2 MB • Updated Q4 2025</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" disabled title="Archive Preview">
                                <i class="bi bi-download me-1"></i> View
                            </button>
                        </div>

                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-3 text-primary">
                                    <i class="bi bi-file-earmark-slides-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Marketplace Investor Presentation & Pitch Deck</div>
                                    <small class="text-muted">PDF • 8.6 MB • Executive Summary</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" disabled title="Archive Preview">
                                <i class="bi bi-download me-1"></i> View
                            </button>
                        </div>

                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-3 text-success">
                                    <i class="bi bi-tree-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Environmental & Artisan Fair-Wage Commitment</div>
                                    <small class="text-muted">PDF • 2.1 MB • ESG Governance</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" disabled title="Archive Preview">
                                <i class="bi bi-download me-1"></i> View
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-primary-subtle text-primary fw-semibold rounded-pill px-3 py-1 mb-2">Direct Channel</span>
                            <h4 class="fw-bold mb-2">Investor Relations Contact</h4>
                            <p class="text-muted small mb-4" style="line-height: 1.6;">
                                We welcome inquiries from institutional investors, fund managers, and industry analysts.
                            </p>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-3 d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-light p-2 text-primary">
                                        <i class="bi bi-envelope-fill"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Investor Email</div>
                                        <a href="mailto:ir@larpshop.com" class="fw-semibold text-dark text-decoration-none">ir@larpshop.com</a>
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-light p-2 text-primary">
                                        <i class="bi bi-telephone-fill"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">IR Hotline</div>
                                        <span class="fw-semibold text-dark">+1 (800) 555-5277 (ext. 4)</span>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-light p-2 text-primary">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Corporate Office</div>
                                        <span class="fw-semibold text-dark">1042 Mythic Citadel Way, Suite 400, Austin, TX</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="p-3 bg-light rounded-3 text-muted small">
                            <i class="bi bi-info-circle me-1"></i> Transfer Agent inquiries should include your account holder ID and shareholder registration code.
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
