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

    <title>Edit Admin</title>

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
                                    <h5 class="mb-0">Edit Admin</h5>
                                    <a href="{{ route('admin.admin.index') }}" class="btn btn-secondary">Back</a>
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

                                    <form method="post" action="{{ route('admin.admin.update', $admin->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <!-- Full Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-fullname">Full Name</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        name="name"
                                                        type="text"
                                                        class="form-control @error('name') is-invalid @enderror"
                                                        id="basic-icon-default-fullname"
                                                        placeholder="John Doe"
                                                        value="{{ old('name', $admin->name) }}"
                                                        required
                                                    />
                                                </div>
                                                @error('name')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-email">Email</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        type="email"
                                                        name="email"
                                                        id="basic-icon-default-email"
                                                        class="form-control @error('email') is-invalid @enderror"
                                                        placeholder="john.doe@example.com"
                                                        value="{{ old('email', $admin->email) }}"
                                                        required
                                                    />
                                                </div>
                                                @error('email')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Phone Number -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="basic-icon-default-phone">Phone No</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        type="text"
                                                        name="phone"
                                                        id="basic-icon-default-phone"
                                                        class="form-control @error('phone') is-invalid @enderror"
                                                        placeholder="+963-+971-1234567"
                                                        value="{{ old('phone', $admin->phone) }}"
                                                        required
                                                    />
                                                </div>
                                                @error('phone')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Current Image -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Current Image</label>
                                                <div>
                                                    <img src="{{ asset('storage/' . $admin->img) }}" alt="Admin Image" class="img-thumbnail" style="max-height: 100px;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Birthday -->
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="basic-icon-default-age">Birthday</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        type="date"
                                                        name="birthday"
                                                        id="basic-icon-default-age"
                                                        class="form-control @error('birthday') is-invalid @enderror"
                                                        value="{{ old('birthday', $admin->birthday) }}"
                                                        required
                                                    />
                                                </div>
                                                @error('birthday')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Status -->
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="basic-icon-default-status">Status</label>
                                                <div class="input-group input-group-merge">
                                                    <select
                                                        name="status"
                                                        id="basic-icon-default-status"
                                                        class="form-control @error('status') is-invalid @enderror"
                                                        required
                                                    >
                                                        <option value="" disabled>Select Status</option>
                                                        <option value="active" {{ old('status', $admin->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="inactive" {{ old('status', $admin->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>
                                                @error('status')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Gender -->
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="basic-icon-default-gender">Gender</label>
                                                <div class="input-group input-group-merge">
                                                    <select
                                                        name="gender"
                                                        id="basic-icon-default-gender"
                                                        class="form-control @error('gender') is-invalid @enderror"
                                                        required
                                                    >
                                                        <option value="" disabled>Select Gender</option>
                                                        <option value="male" {{ old('gender', $admin->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                                        <option value="female" {{ old('gender', $admin->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                                    </select>
                                                </div>
                                                @error('gender')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="role">Roles</label>
                                                <div class="input-group input-group-merge">
                                                    <select
                                                        name="role_id"
                                                        id="role"
                                                        class="form-control @error('role_id') is-invalid @enderror"
                                                        required
                                                    >
                                                        <option value="" disabled>Select Role</option>
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->id }}" {{ old('role_id', $admin->role_id) == $role->id ? 'selected' : '' }}>
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('role_id')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Image Upload Input -->
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="basic-icon-default-image">Update Image (Optional)</label>
                                                <div class="input-group">
                                                    <input
                                                        type="file"
                                                        name="img"
                                                        id="basic-icon-default-image"
                                                        class="form-control"
                                                        aria-label="Upload image"
                                                        accept="image/*"
                                                    />
                                                </div>
                                                <div class="form-text">Allowed formats: JPG, PNG, GIF</div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Admin</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Password Update Section -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Update Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="{{ route('admin.admin.update.password', $admin->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="password">New Password</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        type="password"
                                                        name="password"
                                                        id="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        placeholder="Enter new password"
                                                        required
                                                    />
                                                </div>
                                                <div class="form-text">Password must be 8-20 characters and include: uppercase, lowercase, number, and special character.</div>
                                                @error('password')
                                                <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="password_confirmation">Confirm New Password</label>
                                                <div class="input-group input-group-merge">
                                                    <input
                                                        type="password"
                                                        name="password_confirmation"
                                                        id="password_confirmation"
                                                        class="form-control"
                                                        placeholder="Confirm new password"
                                                        required
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Password</button>
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