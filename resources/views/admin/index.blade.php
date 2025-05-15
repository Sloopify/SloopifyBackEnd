<!DOCTYPE html>

<html
    lang="en"
    class="light-style layout-menu-fixed"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="../assets/"
    data-template="vertical-menu-template-free"
>
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Admin Dashboard</title>

    <meta name="description" content="" />

    @include('layouts.Admin.LinkHeader')

</head>

<body>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Spinner -->
        @include('layouts.Admin.spinner')
        
        <!-- Menu -->


        @include('layouts.Admin.Sidebar')

        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->

            @include('layouts.Admin.NavBar')

            <!-- / Navbar -->

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <!-- Content -->

                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="row">
                        <div class="col-lg-12 mb-4 order-0">
                            <div class="card">
                                <div class="d-flex align-items-center row">
                                    <div class="col-12 col-sm-7">
                                        <!-- Align welcome message to the left -->
                                        <div class="card-body d-flex align-items-center justify-content-start" style="min-height: 290px;">
                                            <h3 style="color: #1e2442; margin: 0;">Welcome {{\Illuminate\Support\Facades\Auth::guard('admin')->user()->name}}! 🎉</h3>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-5 text-center">
                                        <div class="card-body pb-0 px-0 px-md-4">
                                            <img
                                                src="{{asset('dashboard_assets/assets/img/sidebar/lap.jpg')}}"
                                                height="290"
                                                alt="View Badge User"
                                                data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                                data-app-light-img="illustrations/man-with-laptop-light.png"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
                                <!-- <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="card-title d-flex align-items-center justify-content-center">
                                            <div class="avatar flex-shrink-0">
                                                <img src="{{ asset('dashboard_assets/assets/img/icons/unicons/wallet-info.png') }}"
                                                     alt="Credit Card"
                                                     class="rounded img-fluid"
                                                     style="max-width: 50px;" />
                                            </div>
                                        </div>
                                        <span class="d-block mt-2">(Satofill)</span>
                                        <h5 class="card-title text-nowrap mb-1"></h5>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- / Content -->

                <!-- Footer -->
            <!-- add footer here  -->
                <!-- / Footer -->

                <div class="content-backdrop fade"></div>
            </div>
            <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
</div>
<!-- / Layout wrapper -->

@include('layouts.Admin.LinkJS')

</body>
</html>
