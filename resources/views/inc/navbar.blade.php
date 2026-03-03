<!-- Main navbar -->
<div class="navbar navbar-expand-md navbar-dark navbar-static">

    <!-- Header with logos -->
    <div class="navbar-header navbar-dark d-none d-md-flex align-items-md-center">
        <div class="navbar-brand navbar-brand-md">
            <a href="http://kingsburypersonnel.com/" class="d-inline-block">
{{--                <img src="{{ asset('global_assets/images/logo_light.png') }}" alt="">--}}
                <h6 style="margin-bottom: 0; margin-left: 25px; color: #ffffff; font-size: 14px;">CRM - KingsBuryPersonnel</h6>
            </a>
        </div>

        <div class="navbar-brand navbar-brand-xs">
            <a href="#" class="d-inline-block">
{{--                <img src="{{ asset('global_assets/images/logo_icon_light.png') }}" alt="">--}}
                <h6 style="margin-bottom: 0; color: #ffffff; font-size: 14px;">CRM</h6>
            </a>
        </div>
    </div>
    <!-- /header with logos -->


    <!-- Mobile controls -->
    <div class="d-flex flex-1 d-md-none">
        <div class="navbar-brand mr-auto">
            <a href="#" class="d-inline-block">
                <img src="{{ asset('global_assets/images/logo_dark.png') }}" alt="">
            </a>
        </div>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>

        <button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
            <i class="icon-paragraph-justify3"></i>
        </button>
    </div>
    <!-- /mobile controls -->


    <!-- Navbar content -->
    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>

            {{--<li class="nav-item dropdown">--}}
                {{--<a href="#" class="navbar-nav-link dropdown-toggle caret-0" data-toggle="dropdown">--}}
                    {{--<i class="icon-people"></i>--}}
                    {{--<span class="d-md-none ml-2">Users</span>--}}
                    {{--<span class="badge badge-mark border-pink-400 ml-auto ml-md-0"></span>--}}
                {{--</a>--}}

                {{--<div class="dropdown-menu dropdown-content wmin-md-300">--}}
                    {{--<div class="dropdown-content-header">--}}
                        {{--<span class="font-weight-semibold">Users online</span>--}}
                        {{--<a href="#" class="text-default"><i class="icon-search4 font-size-base"></i></a>--}}
                    {{--</div>--}}

                    {{--<div class="dropdown-content-body dropdown-scrollable">--}}
                        {{--<ul class="media-list">--}}
                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<a href="#" class="media-title font-weight-semibold">Jordana Ansley</a>--}}
                                    {{--<span class="d-block text-muted font-size-sm">Lead web developer</span>--}}
                                {{--</div>--}}
                                {{--<div class="ml-3 align-self-center"><span class="badge badge-mark border-success"></span></div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<a href="#" class="media-title font-weight-semibold">Will Brason</a>--}}
                                    {{--<span class="d-block text-muted font-size-sm">Marketing manager</span>--}}
                                {{--</div>--}}
                                {{--<div class="ml-3 align-self-center"><span class="badge badge-mark border-danger"></span></div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<a href="#" class="media-title font-weight-semibold">Hanna Walden</a>--}}
                                    {{--<span class="d-block text-muted font-size-sm">Project manager</span>--}}
                                {{--</div>--}}
                                {{--<div class="ml-3 align-self-center"><span class="badge badge-mark border-success"></span></div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<a href="#" class="media-title font-weight-semibold">Dori Laperriere</a>--}}
                                    {{--<span class="d-block text-muted font-size-sm">Business developer</span>--}}
                                {{--</div>--}}
                                {{--<div class="ml-3 align-self-center"><span class="badge badge-mark border-warning-300"></span></div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<a href="#" class="media-title font-weight-semibold">Vanessa Aurelius</a>--}}
                                    {{--<span class="d-block text-muted font-size-sm">UX expert</span>--}}
                                {{--</div>--}}
                                {{--<div class="ml-3 align-self-center"><span class="badge badge-mark border-grey-400"></span></div>--}}
                            {{--</li>--}}
                        {{--</ul>--}}
                    {{--</div>--}}

                    {{--<div class="dropdown-content-footer bg-light">--}}
                        {{--<a href="#" class="text-grey mr-auto">All users</a>--}}
                        {{--<a href="#" class="text-grey"><i class="icon-gear"></i></a>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</li>--}}
        </ul>

        <span class="navbar-text ml-md-3 mr-md-auto">
				{{--<span class="badge bg-pink-400 badge-pill">16 orders</span>--}}
			</span>

        <ul class="navbar-nav align-items-center navbar-">
            {{--<li class="nav-item dropdown">--}}
                {{--<a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">--}}
                    {{--<img src="{{ asset('global_assets/images/lang/gb.png') }}" class="img-flag mr-2" alt="">--}}
                    {{--English--}}
                {{--</a>--}}

                {{--<div class="dropdown-menu dropdown-menu-right">--}}
                    {{--<a href="#" class="dropdown-item english active"><img src="{{ asset('global_assets/images/lang/gb.png') }}" class="img-flag" alt=""> English</a>--}}
                    {{--<a href="#" class="dropdown-item ukrainian"><img src="{{ asset('global_assets/images/lang/ua.png') }}" class="img-flag" alt=""> Українська</a>--}}
                    {{--<a href="#" class="dropdown-item deutsch"><img src="{{ asset('global_assets/images/lang/de.png') }}" class="img-flag" alt=""> Deutsch</a>--}}
                    {{--<a href="#" class="dropdown-item espana"><img src="{{ asset('global_assets/images/lang/es.png') }}" class="img-flag" alt=""> España</a>--}}
                    {{--<a href="#" class="dropdown-item russian"><img src="{{ asset('global_assets/images/lang/ru.png') }}" class="img-flag" alt=""> Русский</a>--}}
                {{--</div>--}}
            {{--</li>--}}

            {{--<li class="nav-item dropdown">--}}
                {{--<a href="#" class="navbar-nav-link dropdown-toggle caret-0" data-toggle="dropdown">--}}
                    {{--<i class="icon-bubbles4"></i>--}}
                    {{--<span class="d-md-none ml-2">Messages</span>--}}
                    {{--<span class="badge badge-mark border-pink-400 ml-auto ml-md-0"></span>--}}
                {{--</a>--}}

                {{--<div class="dropdown-menu dropdown-menu-right dropdown-content wmin-md-350">--}}
                    {{--<div class="dropdown-content-header">--}}
                        {{--<span class="font-weight-semibold">Messages</span>--}}
                        {{--<a href="#" class="text-default"><i class="icon-compose"></i></a>--}}
                    {{--</div>--}}

                    {{--<div class="dropdown-content-body dropdown-scrollable">--}}
                        {{--<ul class="media-list">--}}
                            {{--<li class="media">--}}
                                {{--<div class="mr-3 position-relative">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}

                                {{--<div class="media-body">--}}
                                    {{--<div class="media-title">--}}
                                        {{--<a href="#">--}}
                                            {{--<span class="font-weight-semibold">James Alexander</span>--}}
                                            {{--<span class="text-muted float-right font-size-sm">04:58</span>--}}
                                        {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<span class="text-muted">who knows, maybe that would be the best thing for me...</span>--}}
                                {{--</div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3 position-relative">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}

                                {{--<div class="media-body">--}}
                                    {{--<div class="media-title">--}}
                                        {{--<a href="#">--}}
                                            {{--<span class="font-weight-semibold">Margo Baker</span>--}}
                                            {{--<span class="text-muted float-right font-size-sm">12:16</span>--}}
                                        {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<span class="text-muted">That was something he was unable to do because...</span>--}}
                                {{--</div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<div class="media-title">--}}
                                        {{--<a href="#">--}}
                                            {{--<span class="font-weight-semibold">Jeremy Victorino</span>--}}
                                            {{--<span class="text-muted float-right font-size-sm">22:48</span>--}}
                                        {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<span class="text-muted">But that would be extremely strained and suspicious...</span>--}}
                                {{--</div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<div class="media-title">--}}
                                        {{--<a href="#">--}}
                                            {{--<span class="font-weight-semibold">Beatrix Diaz</span>--}}
                                            {{--<span class="text-muted float-right font-size-sm">Tue</span>--}}
                                        {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<span class="text-muted">What a strenuous career it is that I've chosen...</span>--}}
                                {{--</div>--}}
                            {{--</li>--}}

                            {{--<li class="media">--}}
                                {{--<div class="mr-3">--}}
                                    {{--<img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" width="36" height="36" class="rounded-circle" alt="">--}}
                                {{--</div>--}}
                                {{--<div class="media-body">--}}
                                    {{--<div class="media-title">--}}
                                        {{--<a href="#">--}}
                                            {{--<span class="font-weight-semibold">Richard Vango</span>--}}
                                            {{--<span class="text-muted float-right font-size-sm">Mon</span>--}}
                                        {{--</a>--}}
                                    {{--</div>--}}

                                    {{--<span class="text-muted">Other travelling salesmen live a life of luxury...</span>--}}
                                {{--</div>--}}
                            {{--</li>--}}
                        {{--</ul>--}}
                    {{--</div>--}}

                    {{--<div class="dropdown-content-footer bg-light">--}}
                        {{--<a href="#" class="text-grey mr-auto">All messages</a>--}}
                        {{--<div>--}}
                            {{--<a href="#" class="text-grey" data-popup="tooltip" title="Mark all as read"><i class="icon-radio-unchecked"></i></a>--}}
                            {{--<a href="#" class="text-grey ml-2" data-popup="tooltip" title="Settings"><i class="icon-cog3"></i></a>--}}
                        {{--</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</li>--}}
			
			@canany(['applicant_chat-box'])
                <li class="nav-item dropdown">
                    <div class="nav-item avatar dropdown mr-2">
                        <a class="nav-link dropdown-toggle waves-effect waves-light" id="navbarDropdownMenuLink-5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="badge badge-danger ml-2 total_notifications_new" id="total_notify_count">0</span>
                            <i class="fas fa-bell"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right dropdown-content wmin-md-350">
                            <div class="dropdown-content-header">
                                <span class="font-weight-semibold">Notifications</span>
                                <a href="#" class="float-right" id="markAll">Mark all as read</a>
                            </div>

                            <div class="dropdown-content-body dropdown-scrollable">
                                <ul class="media-list" id="notification_list">

                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            @endcanany

            <li class="nav-item dropdown dropdown-user">
               <!-- <a href="#" class="navbar-nav-link dropdown-toggle float-right" data-toggle="dropdown">
                    <img src="{{ asset('global_assets/images/placeholders/placeholder.jpg') }}" class="rounded-circle" alt="">
                    <span>{{ Auth::user()->name ?? 'unknown' }}</span>
                </a> -->
				
				@php
                    $user = Auth::user();

                    // Generate initials from the user's name
                    $name = $user->name;
                    $words = explode(" ", $name);
                    $initials = "";
                    foreach ($words as $word) {
                        $initials .= strtoupper(substr($word, 0, 1));
                    }

                    // Generate background color based on the first letter
                    $colors = [
                        'A' => '#FF5733', 'B' => '#33FF57', 'C' => '#5733FF', 'D' => '#FF33A5', 'E' => '#33FFA5',
                        'F' => '#FFA533', 'G' => '#33A5FF', 'H' => '#A533FF', 'I' => '#A5FF33', 'J' => '#FF3357',
                        'K' => '#57FF33', 'L' => '#3357FF', 'M' => '#FF5733', 'N' => '#33FF57', 'O' => '#5733FF',
                        'P' => '#FF33A5', 'Q' => '#33FFA5', 'R' => '#FFA533', 'S' => '#33A5FF', 'T' => '#A533FF',
                        'U' => '#A5FF33', 'V' => '#FF3357', 'W' => '#57FF33', 'X' => '#3357FF', 'Y' => '#FF5733',
                        'Z' => '#33FF57'
                    ];
                    $firstLetter = strtoupper(substr($name, 0, 1));
                    $backgroundColor = $colors[$firstLetter] ?? '#ccc'; // Default color if letter not in array
                @endphp

                <a href="#" class="navbar-nav-link dropdown-toggle float-right" data-toggle="dropdown">
                    <div class="rounded-circle initials-avatar" style="background-color: {{ $backgroundColor }};">
                        {{ $initials }}
                    </div>
                    <span>{{ $user->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    {{--<a href="#" class="dropdown-item"><i class="icon-user-plus"></i> My profile</a>--}}
                    {{--<a href="#" class="dropdown-item"><i class="icon-coins"></i> My balance</a>--}}
                    {{--<a href="#" class="dropdown-item"><i class="icon-comment-discussion"></i> Messages <span class="badge badge-pill bg-indigo-400 ml-auto">58</span></a>--}}
                    {{--<div class="dropdown-divider"></div>--}}
                    {{--<a href="#" class="dropdown-item"><i class="icon-cog5"></i> Account settings</a>--}}
                    <a href="#" class="dropdown-item"
                       onclick="event.preventDefault();
                           document.getElementById('logout-form').submit();"
                    ><i class="icon-switch2" ></i> Logout</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                    </form>
                </div>
            </li>
        </ul>
		
    </div>
    <!-- /navbar content -->
</div>
<!-- /main navbar -->



