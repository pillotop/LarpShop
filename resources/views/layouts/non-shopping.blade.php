@extends('layouts.app')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
    ])
    @yield('more')
@endsection

@section('content')
    <main>

        @yield('idk')

    </main>
@endsection
