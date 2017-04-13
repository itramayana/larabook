<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Larabook</title>

    <!-- Css -->
    <link href="{{asset('css/font-awesome.min.css')}}" rel='stylesheet' type='text/css'>
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/jquery.dataTables.css') }}" rel="stylesheet">
    <link href="{{asset('css/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{asset('css/selectize.css') }}" rel="stylesheet">
    <link href="{{asset('css/selectize.bootstrap3.css') }}" rel="stylesheet">
    <link href="{{asset('css/app.css')}}" rel="stylesheet">

</head>
<body id="app-layout">
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container">
            <div class="navbar-header">

                <!-- Collapsed Hamburger -->
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                    <span class="sr-only">Toggle Navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <!-- Branding Image -->
                <a class="navbar-brand" href="{{ url('/') }}">
                    Larabook
                </a>
            </div>

            <div class="collapse navbar-collapse" id="app-navbar-collapse">
                <!-- Left Side Of Navbar -->
                <ul class="nav navbar-nav">

                    <!--menampilkan link dashboard-->
                    <li><a href="{{ url('/home') }}">Dashboard</a></li>

                    <!--Menampilkan Link 'penulis' ketika mesuk sebagai user admin -->
                    @role('admin')
                    <li><a href="{{ route('admin.authors.index') }}">Penulis</a></li>
                    <li><a href="{{route('admin.books.index')}}">Buku</a> </li>
                    <li><a href="{{route('admin.members.index')}}">Member</a> </li>
                    @endrole
                    @if(auth()->check())
                        <li><a href="{{url('settings/profile')}}">Profil</a> </li>
                    @endif

                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <!-- Authentication Links -->
                    @if (Auth::guest())
                        <li><a href="{{ url('/login') }}">Login</a></li>
                        <li><a href="{{ url('/register') }}">Daftar</a></li>
                    @else
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ url('/settings/password') }}"><i class="fa fa-btn fa-lock"></i>Ubah Password</a></li>
                                <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    @include('layouts._flash')
    @yield('content')
    <script src="{{asset('js/jquery-2.2.2.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('js/dataTables.bootstrap.min.js')}}"></script>
    <script src="{{asset('js/selectize.min.js')}}"></script>
    <script src="{{asset('js/app.js')}}"></script>

    @yield('scripts')
</body>
</html>
