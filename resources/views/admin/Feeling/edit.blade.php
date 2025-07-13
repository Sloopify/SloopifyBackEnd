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

    <title>Edit Feeling</title>

    <meta name="description" content="" />

    @include('layouts.Admin.LinkHeader')

    <style>
        .btn-primary,
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary:visited {
            background-color: #14B8A6 !important;
            border-color: #14B8A6 !important;
        }
        .btn-primary:hover {
            background-color: #0d9488 !important;
            border-color: #0d9488 !important;
        }
        .btn-outline-secondary,
        .btn-outline-secondary:focus,
        .btn-outline-secondary:active,
        .btn-outline-secondary:visited {
            color: #14B8A6 !important;
            border-color: #14B8A6 !important;
            background-color: transparent !important;
        }
        .btn-outline-secondary:hover {
            background-color: #14B8A6 !important;
            color: #fff !important;
            border-color: #14B8A6 !important;
        }
        .btn-secondary,
        .btn-secondary:focus,
        .btn-secondary:active,
        .btn-secondary:visited {
            background-color: #14B8A6 !important;
            border-color: #14B8A6 !important;
            color: #fff !important;
        }
        .btn-secondary:hover {
            background-color: #0d9488 !important;
            border-color: #0d9488 !important;
            color: #fff !important;
        }
        .dropdown-menu .dropdown-item.btn:hover,
        .dropdown-menu .dropdown-item.btn:focus {
            background-color: #14B8A6 !important;
            color: #fff !important;
        }
        .pagination-primary .page-link,
        .pagination-primary .page-link:focus,
        .pagination-primary .page-link:active {
            color: #14B8A6 !important;
        }
        .pagination-primary .page-item.active .page-link {
            background-color: #14B8A6 !important;
            border-color: #14B8A6 !important;
            color: #fff !important;
        }
        .pagination-primary .page-link:hover {
            background-color: #14B8A6 !important;
            color: #fff !important;
            border-color: #14B8A6 !important;
        }
    </style>
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
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Edit Feeling</h5>
                                    <a href="{{ route('admin.feeling.index') }}" class="btn btn-secondary">Back</a>
                                </div>
                                <div class="card-body">

                                    {{-- message Section --}}
                                    @if (session('error_message'))
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            {{ session('error_message') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    @endif
                                    {{-- end message Section --}}

                                    <!-- Display Validation Error Messages -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="post" action="{{ route('admin.feeling.update', $feeling->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <!-- Feeling Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-name">Feeling Name</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        name="name"
                                                        type="text"
                                                        class="form-control @error('name') is-invalid @enderror"
                                                        id="basic-icon-default-name"
                                                        placeholder="e.g., Happy"
                                                        value="{{ old('name', $feeling->name) }}"
                                                        required
                                                    />
                                                </div>
                                                @error('name')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Status -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-status">Status</label>
                                                <div class="input-group input-group-merge">
                                                    <select
                                                        name="status"
                                                        id="basic-icon-default-status"
                                                        class="form-control @error('status') is-invalid @enderror"
                                                        required
                                                    >
                                                        <option value="" disabled>Select Status</option>
                                                        <option value="active" {{ old('status', $feeling->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="inactive" {{ old('status', $feeling->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>
                                                @error('status')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Current Web Icon -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Current Web Icon</label>
                                                <div>
                                                    @if($feeling->web_icon)
                                                        <img src="{{ asset('storage/' . $feeling->web_icon) }}" alt="Web Icon" class="img-thumbnail" style="max-height: 100px;">
                                                    @else
                                                        <span class="text-muted">No web icon uploaded</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Current Mobile Icon -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Current Mobile Icon</label>
                                                <div>
                                                    @if($feeling->mobile_icon)
                                                        <img src="{{ asset('storage/' . $feeling->mobile_icon) }}" alt="Mobile Icon" class="img-thumbnail" style="max-height: 100px;">
                                                    @else
                                                        <span class="text-muted">No mobile icon uploaded</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Web Icon Upload -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-web-icon">Update Web Icon (Optional)</label>
                                                <div class="input-group">
                                                    <input
                                                        type="file"
                                                        name="web_icon"
                                                        id="basic-icon-default-web-icon"
                                                        class="form-control @error('web_icon') is-invalid @enderror"
                                                        aria-label="Upload web icon"
                                                        accept="image/*,.svg"
                                                    />
                                                </div>
                                                <div class="form-text">Allowed formats: JPG, PNG, GIF, SVG</div>
                                                @error('web_icon')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Mobile Icon Upload -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-mobile-icon">Update Mobile Icon (Optional)</label>
                                                <div class="input-group">
                                                    <input
                                                        type="file"
                                                        name="mobile_icon"
                                                        id="basic-icon-default-mobile-icon"
                                                        class="form-control @error('mobile_icon') is-invalid @enderror"
                                                        aria-label="Upload mobile icon"
                                                        accept="image/*,.svg"
                                                    />
                                                </div>
                                                <div class="form-text">Allowed formats: JPG, PNG, GIF, SVG</div>
                                                @error('mobile_icon')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Feeling</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- / Content -->

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
