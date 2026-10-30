@extends('layouts.app')

@section('title', 'Forgot Password')

@section('head')

    @vite('resources/css/company_footer.css')
@endsection

@section('tail')
    @vite('resources/js/components/auth_form.js')
@endsection

@section('content')
    <main>
        <div class="vh-100 d-flex">
            <div class="row w-100 h-100">
                <div class="col-12 col-lg-5 position-relative">
                    <div class="position-absolute top-50 start-50 translate-middle-x">
                        <header class="h1">
                            Enter code
                        </header>
                    </div>
                </div>
                <div class="col-12 col-lg-7 mx-auto h-100 d-flex align-items-center justify-content-center" style="background-color: var(--color-primary-200)">
                    <div style="max-width: 480px;">
                        <x-auth.forgot-password-form/>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <x-company-footer/>
@endsection
