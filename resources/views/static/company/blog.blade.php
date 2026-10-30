@extends('layouts.app')

@section('title', 'The Tavern Chronicle - Blog | LarpShop')

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
        .blog-hero {
            background: linear-gradient(135deg, #1b263b 0%, #0d1b2a 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 3.5rem 2rem;
        }
        .featured-post-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            overflow: hidden;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .featured-post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }
        .article-card {
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            background: #ffffff;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
            height: 100%;
            display: flex;
            flex-column: column;
        }
        .article-card:hover {
            transform: translateY(-4px);
            border-color: #02c8f5;
            box-shadow: 0 8px 24px rgba(2, 200, 245, 0.12);
        }
        .article-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .article-thumb-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 2.5rem;
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
            {{ Breadcrumbs::render('blog') }}
        </div>

        {{-- Hero Header --}}
        <section class="blog-hero mb-5 text-center">
            <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-2 rounded-pill fw-semibold mb-3 border border-warning border-opacity-25">
                <i class="bi bi-feather me-1"></i> The Tavern Chronicle
            </span>
            <h1 class="display-5 fw-bold mb-3">Tales, Crafting Guides & Battle Reports</h1>
            <p class="lead text-light-emphasis mx-auto mb-0" style="max-width: 650px;">
                Insights from master armorers, tips for costume aging, rule breakdowns for major events, and spotlights on independent artisans.
            </p>
        </section>

        {{-- Featured Article --}}
        <section class="mb-5">
            <div class="featured-post-card shadow-sm">
                <div class="row g-0">
                    <div class="col-lg-6 bg-dark text-white p-5 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-danger rounded-pill px-3 py-1">Featured Guide</span>
                            <span class="text-white-50 small"><i class="bi bi-clock me-1"></i> 7 min read</span>
                        </div>
                        <h2 class="fw-bold mb-3">The Definitive Guide to Combat Foam Weapons: Latex, EVA & Hybrid Cores</h2>
                        <p class="text-light-emphasis mb-4" style="line-height: 1.7;">
                            Stepping onto the battlefield for your first weekend war? Learn the crucial differences between injection-molded PU foams, hand-carved EVA, fiberglass reinforcements, and how to pass weapon inspections every time.
                        </p>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-white">Gareth Ironwood</div>
                                    <small class="text-white-50">Master Smith & Safety Marshal</small>
                                </div>
                            </div>
                            <span class="text-white-50 small">October 18, 2026</span>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light p-5 text-center">
                        <div>
                            <div class="display-1 text-primary opacity-50 mb-3">
                                <i class="bi bi-shield-shaded"></i>
                            </div>
                            <h4 class="fw-bold text-dark">Safety First, Glory Second</h4>
                            <p class="text-muted small mx-auto" style="max-width: 360px;">
                                Discover why multi-density foam layering and safe thrusting tips have revolutionized modern live-action combat.
                            </p>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-dark rounded-pill px-4 btn-sm fw-semibold mt-2">
                                Explore Certified Weapons
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Filter Categories --}}
        <section class="mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-semibold small text-muted me-2">Browse By Topic:</span>
                <button type="button" class="btn btn-dark btn-sm rounded-pill px-3">All Articles</button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Crafting & DIY</button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Leather Armor</button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Event Dispatches</button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Artisan Spotlights</button>
            </div>
        </section>

        {{-- Article Grid --}}
        <section class="mb-5">
            <div class="row g-4">
                {{-- Card 1 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff;">
                            <i class="bi bi-droplet-half"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">Leather Care</span>
                                    <small class="text-muted">5 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">How to Weather & Protect Full-Grain Leather from Rainy Battles</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    Rain and mud can ruin untreated cuirasses. Here are essential beeswax and oiling routines that keep your armor supple yet water-resistant.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Laura of Elden</span>
                                <span>Oct 12, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Card 2 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%); color: #ffffff;">
                            <i class="bi bi-hammer"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small">Maker Spotlight</span>
                                    <small class="text-muted">6 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">Artisan Spotlight: The Forge of Valyria & Hand-Poured Resin Gems</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    Meet Tobias Thorne, the artisan whose glowing runic daggers and lightweight wizard staves have taken conventions by storm.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Editorial Team</span>
                                <span>Oct 05, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Card 3 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #7c2d12 0%, #f97316 100%); color: #ffffff;">
                            <i class="bi bi-scissors"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1 small">Garb & Costuming</span>
                                    <small class="text-muted">4 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">Costume Aging 101: Adding Realistic Mud, Grime & Tea-Staining</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    Nothing breaks roleplay immersion quite like a blindingly white medieval tunic. Master the subtle art of tea dyeing, sanding, and charcoal dust.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Melissa Brand</span>
                                <span>Sep 28, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Card 4 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #4c1d95 0%, #8b5cf6 100%); color: #ffffff;">
                            <i class="bi bi-flag-fill"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-purple-subtle text-primary rounded-pill px-2 py-1 small">Event Dispatch</span>
                                    <small class="text-muted">8 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">Conquest of Mythodea 2026: Field Recap & Gear Performance</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    With over 10,000 players on the field, we test which armor configurations best balanced heat dissipation, movement speed, and puncture resistance.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Sir Cedric</span>
                                <span>Sep 18, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Card 5 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #111827 0%, #4b5563 100%); color: #ffffff;">
                            <i class="bi bi-shield-x"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1 small">Safety Standards</span>
                                    <small class="text-muted">5 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">5 Common Weapon Check Mistakes and How to Avoid Failing</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    From core rattles to tip delamination, weapon marshals share their top checklist items so your weapons never get barred from the battlefield.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Marshal Kenneth</span>
                                <span>Sep 09, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Card 6 --}}
                <div class="col-md-6 col-lg-4">
                    <article class="article-card shadow-sm">
                        <div class="article-thumb-placeholder" style="background: linear-gradient(135deg, #0e7490 0%, #06b6d4 100%); color: #ffffff;">
                            <i class="bi bi-lightbulb-fill"></i>
                        </div>
                        <div class="p-4 card-body">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2 py-1 small">Roleplay Advice</span>
                                    <small class="text-muted">4 min read</small>
                                </div>
                                <h5 class="fw-bold mb-2">Character Concepting: Crafting Memorable Low-Fantasy Personas</h5>
                                <p class="text-muted small mb-3" style="line-height: 1.6;">
                                    Why simple, flawed characters with strong personal motivations make for vastly more memorable roleplay than overpowered wandering demigods.
                                </p>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span><i class="bi bi-person me-1"></i> Bryan Croft</span>
                                <span>Aug 24, 2026</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Newsletter Widget --}}
        <section class="card border-0 rounded-4 shadow-sm bg-light p-4 p-md-5 text-center">
            <div class="mx-auto" style="max-width: 580px;">
                <div class="fs-1 text-primary mb-2">
                    <i class="bi bi-envelope-paper-heart"></i>
                </div>
                <h3 class="fw-bold mb-2">Subscribe to The Tavern Chronicle</h3>
                <p class="text-muted mb-4">
                    Receive weekly crafting blueprints, safety updates, and exclusive discount codes from verified artisans directly in your inbox.
                </p>
                <form class="d-flex flex-column flex-sm-row gap-2 justify-content-center" onsubmit="event.preventDefault(); alert('Thank you for subscribing to The Tavern Chronicle!');">
                    <input type="email" class="form-control rounded-pill px-3" placeholder="Enter your email address" required style="max-width: 380px;">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm text-nowrap">
                        Join Dispatches
                    </button>
                </form>
                <small class="text-muted mt-2 d-block">No spam, ever. Unsubscribe with one click anytime.</small>
            </div>
        </section>
    </main>

    <x-side-panel/>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
