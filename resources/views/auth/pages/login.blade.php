<!DOCTYPE html>
<html lang="en" translate="no" data-theme="i3">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/snapsvg@0.5.1/dist/snap.svg-min.min.js"></script>
        <link rel="stylesheet" href="/css/preview_box.css">
        <link rel="stylesheet" href="/css/app.css">
        <script src="/js/app.js" defer></script>
        <title> Register & Login </title>
        <meta name="title" content="Register & Login">
    </head>

    <body>
        <div class="app">
            <nav class="primary-container sticky-top border-bottom p-3">
                <div class="container d-flex justify-content-between">
                    <div class="d-flex align-items-center">
                        <div>
                            <a href="/">
                                <img height="64" width="64" src="/images/larp_shop.png">
                            </a>
                        </div>
                        <div>
                            <ul class="m-0 d-inline-block d-lg-flex align-items-center gap-4" style="list-style-type: none;">
                                <div class="d-flex align-items-center gap-4">
                                    <li>
                                        <button id="login-trigger-products" class="hover-button d-flex align-items-center border-0 p-1 rounded-2">
                                            Products
                                            <svg id="dropdown-arrow-products" class="dropdown-arrow" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                                <path id="dropdown-arrow-path-products" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                                            </svg>
                                        </button>
                                    </li>
                                    <li>
                                        <button id="login-trigger-pricing" class="hover-button d-flex align-items-center border-0 p-1 rounded-2">
                                            Pricing
                                            <svg id="dropdown-arrow-pricing" class="dropdown-arrow" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                                <path id="dropdown-arrow-path-pricing" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                                            </svg>
                                        </button>
                                    </li>
                                    <li>
                                        <button id="login-trigger-enterprise" class="hover-button d-flex align-items-center border-0 p-1 rounded-2">
                                            Enterprise
                                            <svg id="dropdown-arrow-enterprise" class="dropdown-arrow" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                                <path id="dropdown-arrow-path-enterprise" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                                            </svg>
                                        </button>
                                    </li>
                                    <li>
                                        <button id="login-trigger-events" class="hover-button d-flex align-items-center border-0 p-1 rounded-2">
                                            Events
                                            <svg id="dropdown-arrow-events" class="dropdown-arrow" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                                <path id="dropdown-arrow-path-events" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                                            </svg>
                                        </button>
                                    </li>
                                </div>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            <main class="position-relative overflow-hidden">
                <section class="position-absolute top-0 p-xl-5 p-lg-4 p-md-3 p-sm-2 p-1 h-100">
                    <svg class="larp-shop-vertical d-xxl-block d-none z-0" viewBox="0 0 65.990994 509.89802" version="1.1" id="svg1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
                        <g id="svgGroup" stroke-linecap="round" fill-rule="nonzero" fill="#02c8f5" stroke="#00000000" stroke-width="0.5" transform="rotate(90,46.306995,39.034007)">
                            <g id="line-0">
                                <path id="char-0-0" d="M 24.756,86.609 H 5.273 V 22.156 h 7.581 v 58.191 h 11.902 z" />
                                <path id="char-0-1" d="m 64.497,86.609 h -7.214 l 9.778,-64.453 h 9.118 L 86.836,86.609 H 79.695 L 77.864,74.304 H 66.182 Z m 7.214,-52.515 -4.687,34.058 h 9.888 L 71.821,34.094 Z" />
                                <path id="char-0-2" d="M 131.521,86.609 H 123.94 V 22.156 h 13.514 q 5.127,0 7.58,1.813 2.454,1.812 3.205,5.456 0.75,3.644 0.75,9.174 v 0 q 0,5.969 -1.208,9.484 -1.209,3.516 -4.688,4.542 v 0 q 1.758,0.292 2.912,1.501 1.153,1.208 1.812,3.772 0.66,2.563 0.934,6.866 0.275,4.303 0.275,10.822 v 0 q 0,0.622 0,1.794 0,1.172 0.037,2.564 0.036,1.391 0.091,2.746 0.055,1.355 0.165,2.399 0.11,1.044 0.293,1.52 v 0 h -7.544 q -0.147,-0.33 -0.256,-1.465 -0.11,-1.135 -0.184,-3.003 -0.073,-1.868 -0.109,-4.449 -0.037,-2.582 -0.037,-5.786 0,-3.205 0,-7.013 v 0 q 0,-3.663 -0.311,-5.585 -0.312,-1.923 -1.465,-2.673 -1.154,-0.751 -3.681,-0.898 v 0 l -4.504,-0.256 z m 0,-58.447 v 21.753 h 4.834 q 2.087,0 3.204,-0.824 1.117,-0.824 1.52,-3.296 0.403,-2.472 0.403,-7.416 v 0 -0.44 q 0,-4.101 -0.385,-6.207 -0.384,-2.106 -1.483,-2.838 -1.098,-0.732 -3.332,-0.732 v 0 z" />
                                <path id="char-0-3" d="m 195.908,86.609 h -7.58 V 22.156 h 12.964 q 3.845,0 6.243,1.282 2.399,1.281 3.681,3.698 1.282,2.417 1.739,5.823 0.458,3.406 0.458,7.654 v 0 0.989 q 0,6.481 -1.007,10.876 -1.007,4.395 -3.625,6.61 -2.619,2.216 -7.489,2.216 v 0 h -5.384 z m 0,-58.447 v 26.843 h 4.614 q 2.234,0 3.37,-1.264 1.135,-1.263 1.556,-4.193 0.421,-2.929 0.421,-7.946 v 0 -1.246 q 0,-6.225 -0.952,-9.21 -0.952,-2.984 -4.211,-2.984 v 0 z" />
                                <path id="char-0-5" d="m 309.558,87.341 v 0 q -3.918,0 -6.372,-1.41 -2.454,-1.41 -3.772,-4.01 -1.318,-2.6 -1.813,-6.134 -0.494,-3.534 -0.494,-7.818 v 0 -6.262 h 7.361 v 6.555 q 0,6.775 0.97,9.686 0.971,2.911 4.193,2.911 v 0 q 2.234,0 3.278,-1.043 1.044,-1.044 1.337,-3.351 0.293,-2.307 0.293,-6.079 v 0 q 0,-4.322 -1.52,-7.434 -1.52,-3.113 -4.376,-5.97 v 0 l -4.212,-4.211 q -1.831,-1.868 -3.589,-4.083 -1.758,-2.216 -2.911,-5.036 -1.154,-2.819 -1.154,-6.445 v 0 q 0,-4.871 1.026,-8.423 1.025,-3.552 3.735,-5.493 2.71,-1.941 7.691,-1.941 v 0 q 3.918,0 6.28,1.355 2.362,1.355 3.552,3.864 1.19,2.508 1.575,5.951 0.385,3.442 0.385,7.617 v 0 3.808 h -7.251 v -4.321 q 0,-3.918 -0.312,-6.537 -0.311,-2.618 -1.263,-3.937 -0.952,-1.318 -2.966,-1.318 v 0 q -1.832,0 -2.985,0.897 -1.154,0.897 -1.666,2.765 -0.513,1.868 -0.513,4.798 v 0 q 0,2.563 0.623,4.229 0.622,1.667 1.885,3.095 1.264,1.428 3.095,3.369 v 0 l 4.395,4.651 q 3.479,3.625 5.694,8.111 2.216,4.486 2.216,10.419 v 0 q 0,5.347 -1.044,9.192 -1.044,3.845 -3.717,5.914 -2.673,2.069 -7.654,2.069 z" />
                                <path id="char-0-6" d="M 367.061,86.609 H 359.48 V 22.156 h 7.581 v 27.575 h 10.327 V 22.156 h 7.58 v 64.453 h -7.58 V 55.92 h -10.327 z" />
                                <path id="char-0-7" d="m 437.6,87.341 v 0 q -4.834,0 -7.635,-2.124 -2.802,-2.124 -3.992,-6.408 -1.19,-4.285 -1.19,-10.73 v 0 -28.418 q 0,-5.567 1.098,-9.668 1.099,-4.102 3.919,-6.372 2.82,-2.271 7.983,-2.271 v 0 q 3.882,0 6.336,1.3 2.453,1.3 3.79,3.699 1.337,2.399 1.849,5.768 0.513,3.369 0.513,7.544 v 0 28.418 q 0,6.445 -1.154,10.73 -1.153,4.284 -3.918,6.408 -2.765,2.124 -7.599,2.124 z m 0,-6.518 v 0 q 2.088,0 3.186,-1.19 1.099,-1.191 1.502,-3.974 0.402,-2.783 0.402,-7.507 v 0 -28.931 q 0,-5.786 -0.915,-8.569 -0.916,-2.783 -3.992,-2.783 v 0 q -3.186,0 -4.303,2.765 -1.117,2.764 -1.117,8.551 v 0 28.967 q 0,4.761 0.403,7.525 0.403,2.765 1.538,3.956 1.135,1.19 3.296,1.19 z" />
                                <path id="char-0-8" d="m 497.666,86.609 h -7.581 V 22.156 h 12.964 q 3.846,0 6.244,1.282 2.399,1.281 3.681,3.698 1.281,2.417 1.739,5.823 0.458,3.406 0.458,7.654 v 0 0.989 q 0,6.481 -1.007,10.876 -1.007,4.395 -3.626,6.61 -2.618,2.216 -7.489,2.216 v 0 h -5.383 z m 0,-58.447 v 26.843 h 4.614 q 2.234,0 3.369,-1.264 1.136,-1.263 1.557,-4.193 0.421,-2.929 0.421,-7.946 v 0 -1.246 q 0,-6.225 -0.952,-9.21 -0.952,-2.984 -4.212,-2.984 v 0 z" />
                            </g>
                        </g>
                    </svg>
                </section>
                <section class="container z-0 border-start-dashed border-end-dashed border-thin overflow-hidden">
                    <header>
                        <h1 class="my-4"> The next generation e-commerce platform </h1>
                        <svg class="larp-shop-horizontal d-xxl-none d-block" width="509.89801" height="65.990982" viewBox="-2 -2 509.89798 65.990983" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
                            <g stroke-linecap="round" fill-rule="nonzero" fill="#02c8f5" stroke="#00000000" stroke-width="0.5" transform="translate(-7.2730062,-23.350003)">
                                <g>
                                    <path d="M 24.756,86.609 H 5.273 V 22.156 h 7.581 v 58.191 h 11.902 z" />
                                    <path d="m 64.497,86.609 h -7.214 l 9.778,-64.453 h 9.118 L 86.836,86.609 H 79.695 L 77.864,74.304 H 66.182 Z m 7.214,-52.515 -4.687,34.058 h 9.888 L 71.821,34.094 Z" />
                                    <path d="M 131.521,86.609 H 123.94 V 22.156 h 13.514 q 5.127,0 7.58,1.813 2.454,1.812 3.205,5.456 0.75,3.644 0.75,9.174 v 0 q 0,5.969 -1.208,9.484 -1.209,3.516 -4.688,4.542 v 0 q 1.758,0.292 2.912,1.501 1.153,1.208 1.812,3.772 0.66,2.563 0.934,6.866 0.275,4.303 0.275,10.822 v 0 q 0,0.622 0,1.794 0,1.172 0.037,2.564 0.036,1.391 0.091,2.746 0.055,1.355 0.165,2.399 0.11,1.044 0.293,1.52 v 0 h -7.544 q -0.147,-0.33 -0.256,-1.465 -0.11,-1.135 -0.184,-3.003 -0.073,-1.868 -0.109,-4.449 -0.037,-2.582 -0.037,-5.786 0,-3.205 0,-7.013 v 0 q 0,-3.663 -0.311,-5.585 -0.312,-1.923 -1.465,-2.673 -1.154,-0.751 -3.681,-0.898 v 0 l -4.504,-0.256 z m 0,-58.447 v 21.753 h 4.834 q 2.087,0 3.204,-0.824 1.117,-0.824 1.52,-3.296 0.403,-2.472 0.403,-7.416 v 0 -0.44 q 0,-4.101 -0.385,-6.207 -0.384,-2.106 -1.483,-2.838 -1.098,-0.732 -3.332,-0.732 v 0 z" />
                                    <path d="m 195.908,86.609 h -7.58 V 22.156 h 12.964 q 3.845,0 6.243,1.282 2.399,1.281 3.681,3.698 1.282,2.417 1.739,5.823 0.458,3.406 0.458,7.654 v 0 0.989 q 0,6.481 -1.007,10.876 -1.007,4.395 -3.625,6.61 -2.619,2.216 -7.489,2.216 v 0 h -5.384 z m 0,-58.447 v 26.843 h 4.614 q 2.234,0 3.37,-1.264 1.135,-1.263 1.556,-4.193 0.421,-2.929 0.421,-7.946 v 0 -1.246 q 0,-6.225 -0.952,-9.21 -0.952,-2.984 -4.211,-2.984 v 0 z" />
                                    <path d="m 309.558,87.341 v 0 q -3.918,0 -6.372,-1.41 -2.454,-1.41 -3.772,-4.01 -1.318,-2.6 -1.813,-6.134 -0.494,-3.534 -0.494,-7.818 v 0 -6.262 h 7.361 v 6.555 q 0,6.775 0.97,9.686 0.971,2.911 4.193,2.911 v 0 q 2.234,0 3.278,-1.043 1.044,-1.044 1.337,-3.351 0.293,-2.307 0.293,-6.079 v 0 q 0,-4.322 -1.52,-7.434 -1.52,-3.113 -4.376,-5.97 v 0 l -4.212,-4.211 q -1.831,-1.868 -3.589,-4.083 -1.758,-2.216 -2.911,-5.036 -1.154,-2.819 -1.154,-6.445 v 0 q 0,-4.871 1.026,-8.423 1.025,-3.552 3.735,-5.493 2.71,-1.941 7.691,-1.941 v 0 q 3.918,0 6.28,1.355 2.362,1.355 3.552,3.864 1.19,2.508 1.575,5.951 0.385,3.442 0.385,7.617 v 0 3.808 h -7.251 v -4.321 q 0,-3.918 -0.312,-6.537 -0.311,-2.618 -1.263,-3.937 -0.952,-1.318 -2.966,-1.318 v 0 q -1.832,0 -2.985,0.897 -1.154,0.897 -1.666,2.765 -0.513,1.868 -0.513,4.798 v 0 q 0,2.563 0.623,4.229 0.622,1.667 1.885,3.095 1.264,1.428 3.095,3.369 v 0 l 4.395,4.651 q 3.479,3.625 5.694,8.111 2.216,4.486 2.216,10.419 v 0 q 0,5.347 -1.044,9.192 -1.044,3.845 -3.717,5.914 -2.673,2.069 -7.654,2.069 z" />
                                    <path d="M 367.061,86.609 H 359.48 V 22.156 h 7.581 v 27.575 h 10.327 V 22.156 h 7.58 v 64.453 h -7.58 V 55.92 h -10.327 z" />
                                    <path d="m 437.6,87.341 v 0 q -4.834,0 -7.635,-2.124 -2.802,-2.124 -3.992,-6.408 -1.19,-4.285 -1.19,-10.73 v 0 -28.418 q 0,-5.567 1.098,-9.668 1.099,-4.102 3.919,-6.372 2.82,-2.271 7.983,-2.271 v 0 q 3.882,0 6.336,1.3 2.453,1.3 3.79,3.699 1.337,2.399 1.849,5.768 0.513,3.369 0.513,7.544 v 0 28.418 q 0,6.445 -1.154,10.73 -1.153,4.284 -3.918,6.408 -2.765,2.124 -7.599,2.124 z m 0,-6.518 v 0 q 2.088,0 3.186,-1.19 1.099,-1.191 1.502,-3.974 0.402,-2.783 0.402,-7.507 v 0 -28.931 q 0,-5.786 -0.915,-8.569 -0.916,-2.783 -3.992,-2.783 v 0 q -3.186,0 -4.303,2.765 -1.117,2.764 -1.117,8.551 v 0 28.967 q 0,4.761 0.403,7.525 0.403,2.765 1.538,3.956 1.135,1.19 3.296,1.19 z" />
                                    <path d="m 497.666,86.609 h -7.581 V 22.156 h 12.964 q 3.846,0 6.244,1.282 2.399,1.281 3.681,3.698 1.281,2.417 1.739,5.823 0.458,3.406 0.458,7.654 v 0 0.989 q 0,6.481 -1.007,10.876 -1.007,4.395 -3.626,6.61 -2.618,2.216 -7.489,2.216 v 0 h -5.383 z m 0,-58.447 v 26.843 h 4.614 q 2.234,0 3.369,-1.264 1.136,-1.263 1.557,-4.193 0.421,-2.929 0.421,-7.946 v 0 -1.246 q 0,-6.225 -0.952,-9.21 -0.952,-2.984 -4.212,-2.984 v 0 z" />
                                </g>
                            </g>
                        </svg>
                    </header>
                    <div class="d-lg-flex vh-100">
                        <div class="d-block d-lg-flex justify-content-between align-items-center w-100">
                            <div class="position-relative">
                                <div class="position-absolute z-0 top-50 start-50 translate-middle">
                                    <svg width="149.63327mm" height="150.05292mm" viewBox="0 0 149.63327 150.05292" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
                                        <defs id="defs1">
                                            <linearGradient id="swatch10">
                                                <stop style="stop-color:#ffffff;stop-opacity:1;" offset="0" id="stop10" />
                                                <stop style="stop-color:#ffffff;stop-opacity:0;" offset="1" id="stop11" />
                                            </linearGradient>
                                            <mask maskUnits="userSpaceOnUse" id="mask14">
                                                <rect style="opacity:1;fill:url(#radialGradient14);fill-opacity:1;stroke-width:0.169128" id="rect14" width="150" height="151.15375" x="0.36673278" y="-0.80716079" />
                                            </mask>
                                            <radialGradient xlink:href="#swatch10" id="radialGradient14" gradientUnits="userSpaceOnUse" gradientTransform="matrix(0.71605309,0,0,0.72156082,0.1150599,-32.264665)" cx="105.09231" cy="148.3373" fx="105.09231" fy="148.3373" r="104.74083" />
                                        </defs>
                                        <g id="layer1" transform="translate(-0.36673279,0.0529165)">
                                            <path d="M 0,0 V 150 M 13.229167,0 V 150 M 26.458333,0 V 150 M 39.6875,0 V 150 M 52.916666,0 V 150 M 66.145833,0 V 150 M 79.374999,0 V 150 M 92.604166,0 V 150 M 105.83333,0 V 150 M 119.0625,0 V 150 M 132.29167,0 V 150 M 145.52083,0 V 150 M 0,0 H 150 M 0,13.229167 H 150 M 0,26.458333 H 150 M 0,39.6875 H 150 M 0,52.916666 H 150 M 0,66.145833 H 150 M 0,79.374999 H 150 M 0,92.604166 H 150 M 0,105.83333 H 150 M 0,119.0625 H 150 M 0,132.29167 H 150 M 0,145.52083 h 150" style="fill:none;stroke:#000000;stroke-width:0.105833px" id="path14" mask="url(#mask14)" />
                                        </g>
                                    </svg>
                                </div>
                                <div class="primary-container position-relative shadow border rounded-2 p-4">
                                    <h3 class="primary-text"> Sign in or create account </h3>
                                    <form class="container" method="POST" action="{{ route('login') }}">
                                    @csrf
                                        <div class="row row-cols-1 my-2">
                                            <div class="col">
                                                <label for="email">Email address</label>
                                                <input class="form-control" id="email" type="text" name="email" placeholder="Email address">
                                            </div>
                                        </div>
                                        <div class="row row-cols-1 my-2">
                                            <div class="col">
                                                <label for="password">Password</label>
                                                <input class="form-control" id="password" type="text" name="password" placeholder="Password" aria-describedby="description">
                                                <div id="description" class="form-text"> Your password must meet the requirements </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="w-100 border-0 rounded-2 p-2 my-2">
                                            Continue
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="preview-box z-1 end-0 overflow-hidden">
                                <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <div class="carousel-item active">
                                            <div class="position-absolute z-1 start-50 top-50">
                                                <h1 class="fw-bold text-capitalize"> Everything larp-able </h1>
                                            </div>
                                            <div class="image">
                                                <svg width="234.88513mm" height="173.25981mm" viewBox="0 0 234.88513 173.25981" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
                                                    <g style="display:inline" transform="translate(-97.218994,-54.147283)">
                                                        <ellipse style="fill:#f5a802;fill-opacity:1;stroke-width:0.264583" id="path1" ry="56.412426" rx="52.546154" cy="170.99469" cx="260.97339" />
                                                        <ellipse style="mix-blend-mode:color-dodge;fill:#ff7f2a;stroke-width:0.290257" id="path3" cx="295.72601" cy="123.59013" rx="36.378109" ry="36.553844" />
                                                        <ellipse style="fill:#aa4400;stroke-width:0.264583" id="path9" ry="17.749704" rx="16.695267" cy="144.63373" cx="157.28697" />
                                                        <path style="display:inline;fill:#f5024f;fill-opacity:1;stroke-width:0.283676" id="path12" d="m 251.65916,95.602364 a 21.615976,22.494675 0 0 1 -21.60274,22.494666 21.615976,22.494675 0 0 1 -21.6292,-22.467115 21.615976,22.494675 0 0 1 21.57625,-22.522188 21.615976,22.494675 0 0 1 21.65562,22.439534 l -21.61591,0.0551 z" />
                                                        <path style="mix-blend-mode:luminosity;fill:none;fill-rule:evenodd;stroke:#000000;stroke-width:0.264583" id="path10" d="m 239.70888,105.09231 c 1.40981,-0.5463 1.43195,1.63087 0.90799,2.34319 -1.41992,1.93036 -4.31302,1.06431 -5.59438,-0.52722 -2.29206,-2.84686 -0.8795,-6.988538 1.96242,-8.845558 4.17063,-2.725252 9.70629,-0.705573 12.09675,3.397638 3.18611,5.46892 0.53728,12.43978 -4.83284,15.34792 -6.75775,3.65961 -15.18098,0.3721 -18.59911,-6.26804 -4.13982,-8.0421 -0.20875,-17.926561 7.70325,-21.850299 9.32401,-4.623991 20.67487,-0.04655 25.10148,9.138462 5.1107,10.604457 -0.11487,23.424997 -10.57367,28.352667 -11.88396,5.59912 -26.17641,-0.27575 -31.60385,-12.00888 -6.08876,-13.1628 0.43624,-28.92876 13.44409,-34.855031 14.44118,-6.579283 31.6818,0.596429 38.10621,14.879291" transform="rotate(142.72829,248.91002,109.08156)" />
                                                        <rect style="fill:#f5024f;fill-opacity:1;stroke-width:0.26648" id="rect12" width="40" height="40" x="241.4935" y="-126.26191" ry="0" transform="rotate(63.923429)" />
                                                        <path style="fill:none;fill-rule:evenodd;stroke:#000000;stroke-width:0.264583" id="path13" d="m 141.21367,184.28537 c 0.40106,-0.0308 0.23945,0.5254 0.0513,0.66659 -0.50996,0.38261 -1.18033,-0.0602 -1.38445,-0.56404 -0.36513,-0.90119 0.31282,-1.84761 1.17934,-2.10231 1.27165,-0.37377 2.52649,0.5659 2.82017,1.79465 0.39143,1.63775 -0.81874,3.20982 -2.40996,3.53804 -2.00239,0.41303 -3.89534,-1.07139 -4.2559,-3.02527 -0.43668,-2.36642 1.3239,-4.58212 3.64058,-4.97376 2.73013,-0.46154 5.26969,1.57633 5.69163,4.25589 0.48714,3.09366 -1.82871,5.95777 -4.8712,6.40948 -3.45708,0.51327 -6.64623,-2.08103 -7.12735,-5.48651 -0.53974,-3.82042 2.33334,-7.33495 6.10182,-7.8452 4.18372,-0.56649 8.02387,2.58562 8.56307,6.71712" />
                                                        <path style="mix-blend-mode:difference;fill:#f5024f;fill-opacity:1;stroke-width:0.264583" d="m 188.34883,158.4744 c -29.1565,15.53164 -34.84181,8.65565 -34.84181,8.65565 0,0 -42.48539,-23.03097 -2.81432,-25.79596 39.67108,-2.765 59.39373,-6.93115 46.53686,-17.08658 -12.85689,-10.15545 -29.08964,-20.54801 -0.0522,-25.601957 29.03738,-5.053952 51.90902,3.029187 48.92515,19.165487 -2.98386,16.13631 -28.91426,16.47389 -15.6756,34.36649 13.23866,17.89256 29.03629,12.5255 5.30752,21.03645 -23.72876,8.51095 -39.45551,-0.304 -37.31233,-4.47124" />
                                                        <ellipse style="mix-blend-mode:darken;fill:#00a64d;fill-opacity:1;stroke-width:0.265;stroke-dasharray:none" id="path15" cx="184.43919" cy="201.05255" rx="15.844234" ry="16.459543" />
                                                        <circle style="fill:#00a64d;fill-opacity:1;stroke-width:0.264999;stroke-dasharray:none" id="path16" cx="286.58066" cy="67.530281" r="13.382994" />
                                                        <rect style="fill:#00a64d;fill-opacity:1;stroke-width:0.264999;stroke-dasharray:none" id="rect16" width="17.536341" height="17.536345" x="97.218994" y="204.59059" />
                                                    </g>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="carousel-item">
                                            <img src="..." class="d-block w-100" alt="...">
                                        </div>
                                        <div class="carousel-item">
                                            <img src="..." class="d-block w-100" alt="...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <footer class="position-relative fixed-bottom d-block w-100 p-4 pt-0">
                    <div class="container primary-container border-top-dashed border-start-dashed border-end-dashed border-thin">
                        <div class="row row-cols-2 row-cols-lg-3 w-100 my-4">
                            <div class="col">
                                <h4 class="text-uppercase"> COMPANY INFO </h4>
                                <ul class="p-0" style="list-style-type: none;">
                                    <li class="m-3 text-capitalize"> About Larp Shop </li>
                                    <li class="m-3 text-capitalize"> Contact Us </li>
                                    <li class="m-3 text-capitalize"> Investors </li>
                                    <li class="m-3 text-capitalize"> Blog </li>
                                    <li class="m-3 text-capitalize"> Press </li>
                                </ul>
                            </div>
                            <div class="col">
                                <h4 class="text-uppercase"> CUSTOMER SERVICE </h4>
                                <ul class="p-0" style="list-style-type: none;">
                                    <li class="m-3 text-capitalize"> Help Centre </li>
                                    <li class="m-3 text-capitalize"> Privacy Policy </li>
                                    <li class="m-3 text-capitalize"> Community Forum </li>
                                    <li class="m-3 text-capitalize"> Product Recalls </li>
                                </ul>
                            </div>
                            <div class="col">
                                <h4 class="text-uppercase"> CAREER </h4>
                                <ul class="p-0" style="list-style-type: none;">
                                    <li class="m-3 text-capitalize"> Become an accomplice </li>
                                    <li class="m-3 text-capitalize"> Sell products on LarpShop </li>
                                    <li class="m-3 text-capitalize"> Advertise your products</li>
                                    <li class="m-3 text-capitalize"> Larp insurrance </li>
                                </ul>
                            </div>
                        </div>
                        <div class="d-md-flex d-sm-block justify-content-center align-items-center gap-5">
                            <button type="button" class="hover-button p-2 border-0 rounded-3"> Language </button>
                            <div> Terms of Service </div>
                            <div> Legal </div>
                            <div> Privacy Policy </div>
                            <div> Sitemap </div>
                        </div>
                        <div class="d-flex justify-content-center">
                            © 2026 LarpShop
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </body>

</html>
