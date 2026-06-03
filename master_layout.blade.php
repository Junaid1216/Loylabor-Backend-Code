@php
    $staffUser = Auth::guard('staff')->user();
    $defaultAvatar = $setting->default_avatar ?? 'assets/images/default-avatar.png';
    $favicon = $setting->favicon ?? 'assets/images/favicon.png';
    $copyrightText = $setting->copyright_text ?? '© ' . date('Y') . ' All Rights Reserved.';
    $version = $setting->version ?? '1.0.0';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <link type="image/x-icon" href="{{ asset($favicon) }}" rel="shortcut icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('title')
    @include('admin.partials.styles')
    @stack('css')
</head>

<body>
    <div id="app">
        <div class="main-wrapper">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar px-3 py-2">
                <div class="me-2 form-inline">
                    <ul class="navbar-nav d-flex align-items-center">
                        <li><a class="nav-link nav-link-lg" data-toggle="sidebar" href="#"><i
                                    class="fas fa-bars"></i></a></li>
                        <p style="margin:0; margin-right: 10px; padding: 0;  color: #ffffff; font-size: 20px;">Laymadyok
                            Panel</p>
                        <small style="color: #ffffff;"><i class="fas fa-map-marker-alt"></i> Location:
                            {{ Auth::guard('staff')->user()->location ?? 'University of Management & Technology C-II Block C 2 Phase 1 Johar Town, Lahore, 54770, Pakistan' }}</small>
                        <!-- @if (Module::isEnabled('Language') && Route::has('set-language') && allLanguages()?->where('status', 1)->count() > 1)
                            <li class="setLanguageHeader dropdown border rounded-2"><a
                                    class="nav-link dropdown-toggle nav-link-lg nav-link-user" data-bs-toggle="dropdown"
                                    href="javascript:;">
                                    <div class="d-sm-none d-lg-inline-block">
                                        {{ allLanguages()?->firstWhere('code', getSessionLanguage())?->name ?? __('Select language') }}
                                    </div>
                                </a>
                                <div class="dropdown-menu py-0 dropdown-menu-left">
                                    @forelse (allLanguages()?->where('status', 1) as $language)
                                        <a class="dropdown-item has-icon {{ getSessionLanguage() == $language->code ? 'bg-light' : '' }}"
                                            href="{{ getSessionLanguage() == $language->code ? 'javascript:;' : route('set-language', ['code' => $language->code]) }}">
                                            {{ $language->name }}
                                        </a>
                                    @empty
                                        <a class="dropdown-item has-icon {{ getSessionLanguage() == 'en' ? 'bg-light' : '' }}"
                                            href="javascript:;">
                                            {{ __('English') }}
                                        </a>
                                    @endforelse
                                </div>
                            </li>
                        @endif
                        @if (Module::isEnabled('Currency') &&
                                Route::has('set-currency') &&
                                allCurrencies()?->where('status', 'active')->count() > 1)
                            <li class="set-currency-header dropdown border rounded-2 mx-2"><a
                                    class="nav-link dropdown-toggle nav-link-lg nav-link-user" data-bs-toggle="dropdown"
                                    href="javascript:;">
                                    <div class="d-sm-none d-lg-inline-block">
                                        {{ allCurrencies()?->firstWhere('currency_code', getSessionCurrency())?->currency_name ?? __('Select Currency') }}
                                    </div>
                                </a>
                                <div class="dropdown-menu py-0 dropdown-menu-left">
                                    @forelse (allCurrencies()?->where('status', 'active') as $currency)
                                        <a class="dropdown-item has-icon {{ getSessionCurrency() == $currency->currency_code ? 'bg-light' : '' }}"
                                            href="{{ getSessionCurrency() == $currency->currency_code ? 'javascript:;' : route('set-currency', ['currency' => $currency->currency_code]) }}">
                                            {{ $currency->currency_name }}
                                        </a>
                                    @empty
                                        <a class="dropdown-item has-icon {{ getSessionCurrency() == 'USD' ? 'bg-light' : '' }}"
                                            href="javascript:;">
                                            {{ __('USD') }}
                                        </a>
                                    @endforelse
                                </div>
                            </li>
                        @endif -->
                    </ul>
                </div>
                <div class="mr-auto me-md-auto search-box position-relative">
                    <!-- <x-admin.form-input id="search_menu" autocomplete="off" :placeholder="__('Search option')" /> -->

                    <div class="position-absolute d-none rounded-2" id="admin_menu_list">
                        <!-- @foreach (App\Enums\RouteList::getAll() as $route_item)
                            @if (checkAdminHasPermission($route_item?->permission) || empty($route_item?->permission))
                                <a href="{{ $route_item?->route }}"
                                    @isset($route_item->tab)
                                        data-active-tab="{{ $route_item->tab }}" class="border-bottom search-menu-item"
                                    @else
                                        class="border-bottom"
                                    @endisset>{{ $route_item?->name }}</a>
                            @endif
                        @endforeach -->
                        <a class="not-found-message d-none" href="javascript:;">{{ __('Not Found!') }}</a>
                    </div>
                </div>

                <ul class="navbar-nav">
                    <!-- @include('admin.partials.notifications', [
                        'adminNotifications' => Cache::get('admin-notifications', collect([])),
                    ]) -->



                    <li class="dropdown"><a class="nav-link dropdown-toggle nav-link-lg nav-link-user dropdown_user"
                            data-bs-toggle="dropdown" href="javascript:;">
                            @if ($staffUser && $staffUser->image)
                                <img class="me-1 rounded-circle" src="{{ asset($staffUser->image) }}" alt="image">
                            @else
                                <img class="me-1 rounded-circle" src="{{ asset($defaultAvatar) }}" alt="image">
                            @endif

                            <div class="d-sm-none d-lg-inline-block">{{ $staffUser ? $staffUser->name : __('Staff') }}
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right">
                            @if($staffUser)
                                <a class="dropdown-item has-icon d-flex align-items-center {{ isRoute(['staff.edit-profile'], 'text-primary') }}"
                                    href="{{ route('staff.edit-profile') }}">
                                    <i class="far fa-user"></i> {{ __('Profile') }}
                                </a>
                                <a class="dropdown-item has-icon d-flex align-items-center {{ isRoute(['staff.dashboard'], 'text-primary') }}"
                                    href="{{ route('staff.dashboard') }}">
                                    <i class="fas fa-chart-line"></i> {{ __('Dashboard') }}
                                </a>

                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item has-icon d-flex align-items-center" href="javascript:;"
                                    onclick="event.preventDefault(); $('#staff-logout-form').trigger('submit');">
                                    <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                                </a>
                            @else
                                <a class="dropdown-item has-icon d-flex align-items-center text-muted" href="javascript:;">
                                    <i class="fas fa-exclamation-circle"></i> {{ __('Not logged in') }}
                                </a>
                            @endif
                        </div>
                    </li>

                </ul>
            </nav>

            @include('staff.sidebar')

            @yield('staff-content')

            <footer class="main-footer">
                <div class="footer-left">
                    Home-Services-Technicians-Plumbers
                </div>
                <!-- <div class="footer-right">
                    <span>{{ __('version') }}: {{ $version }} ({{ __('Loaded in') }}
                        %%LOAD_TIME%%)</span>
                </div> -->
            </footer>

        </div>
    </div>

    {{-- staff logout form --}}
    @if($staffUser)
        <form class="d-none" id="staff-logout-form" action="{{ route('staff.logout') }}" method="POST">
            @csrf
        </form>
    @endif

    {{-- delete modal --}}
    <x-admin.delete-modal />

    @stack('modals')

    @include('admin.partials.javascripts')
    @include('admin.js-variables')
    @stack('js')

</body>

</html>