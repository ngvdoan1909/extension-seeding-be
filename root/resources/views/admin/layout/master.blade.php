<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg"
    data-sidebar-image="none" data-preloader="disable">

<head>

    <meta charset="utf-8" />
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('admin/assets/images/favicon.ico') }}">

    <!-- jsvectormap css -->
    <link href="{{ asset('admin/assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet"
        type="text/css" />

    <!-- Swiper slider css -->
    <link href="{{ asset('admin/assets/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Layout config Js -->
    <script src="{{ asset('admin/assets/js/layout.js') }}"></script>

    <!-- Bootstrap Css -->
    <link href="{{ asset('admin/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Icons Css -->
    <link href="{{ asset('admin/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App Css -->
    <link href="{{ asset('admin/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Custom Css -->
    <link href="{{ asset('admin/assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

    @yield('style-libs')

    @yield('styles')
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('admin.layout.header')

        <div class="vertical-overlay"></div>
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    @yield('content')

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('admin.layout.footer')
        </div>
        <!-- end main content-->

    </div>

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <script>
        const PATH_ROOT = '{{ asset('admin') }}'
    </script>
    <!-- JAVASCRIPT -->
    <script src="{{ asset('admin/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('admin/assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('admin/assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('admin/assets/js/plugins.js') }}"></script>

    @yield('script-libs')

    @yield('scripts')
    <!-- App js -->
    <script src="{{ asset('admin/assets/js/app.js') }}"></script>

</body>

</html>