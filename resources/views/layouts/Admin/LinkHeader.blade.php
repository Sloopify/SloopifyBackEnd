
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{ asset('dashboard_assets/assets/img/favicon/subzone-logo.svg') }}" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet"
/>

<!-- Icons. Uncomment required icon fonts -->
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/fonts/boxicons.css') }}" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/css/demo.css') }}" />

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

<!-- Page CSS -->

<!-- Helpers -->
<script src="{{ asset('dashboard_assets/assets/vendor/js/helpers.js') }}"></script>

<script src="{{ asset('dashboard_assets/assets/js/config.js') }}"></script>

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

<!-- Page CSS -->
<!-- Page -->
<link rel="stylesheet" href="{{ asset('dashboard_assets/assets/vendor/css/pages/page-auth.css') }}" />

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .circle-image {
        width: 100px; /* Adjust size as needed */
        height: 100px; /* Ensure it remains a circle */
        border-radius: 50%; /* Makes it a perfect circle */
        object-fit: cover; /* Ensures the image fills the circle properly */
        display: block;
    }

    /* Custom Button Style */
    .btn-primary {
        background-color: hsla(0, 0%, 0%, 0.8) !important; /* 50% transparency */
        border: none !important;
        padding: 12px;
        font-size: 16px;
        font-weight: bold;
        transition: 0.3s ease-in-out;
    }

    .btn-primary:hover {
        background-color: hsla(0, 0%, 0%, 0.8) !important; /* 50% transparency */
    }

    /* Custom Input Fields */
    .form-control {
        border: 2px solid #1e2442 !important; /* Light green border */
        border-radius: 8px; /* Rounded corners */
        padding: 10px;
        font-size: 14px;
        transition: border 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .form-control:focus {
        border-color: hsla(0, 0%, 0%, 0.8) !important; /* 50% transparency *//* Darker green when focused */
        /*box-shadow: 0 0 8px rgba(76, 175, 80, 0.5) !important;*/
    }

    /* Custom Checkbox */
    .form-check-input:checked {
        background-color: hsla(0, 0%, 0%, 0.8) !important; /* 50% transparency */
        border-color: hsla(0, 0%, 0%, 0.8) !important; /* 50% transparency */
    }

</style>
