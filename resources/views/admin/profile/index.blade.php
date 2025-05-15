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

    <title>My Profile</title>

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
        .profile-header {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #f5f5f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-info {
            padding-top: 1rem;
        }
        .profile-tabs .nav-item .nav-link.active {
            background-color: #14B8A6 !important;
            color: white !important;
        }
        .profile-tabs .nav-item .nav-link {
            color: #697a8d;
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
                    
                    {{-- message Section --}}
                    @if (session('success_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error_message'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    {{-- end message Section --}}

                    <!-- Profile Header -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card profile-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center">
                                            <img src="{{ asset('storage/' . $admin->img) }}" class="profile-pic mb-3" alt="Profile Image">
                                        </div>
                                        <div class="col-md-9 profile-info">
                                            <h3>{{ $admin->name }}</h3>
                                            <p class="mb-1"><i class="bx bx-envelope me-2"></i>{{ $admin->email }}</p>
                                            <p class="mb-1"><i class="bx bx-phone me-2"></i>{{ $admin->phone }}</p>
                                            <p class="mb-1">
                                                <i class="bx bx-user me-2"></i>
                                                {{ $admin->gender == 'male' ? 'Male' : 'Female' }}
                                            </p>
                                            <p class="mb-1"><i class="bx bx-calendar me-2"></i>{{ $admin->birthday }} ({{ \Carbon\Carbon::parse($admin->birthday)->age }} years)</p>
                                            <p class="mb-1">
                                                <i class="bx bx-check-circle me-2"></i>
                                                <span class="badge bg-{{ $admin->status == 'active' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($admin->status) }}
                                                </span>
                                            </p>
                                            <p class="mb-1"><i class="bx bx-shield me-2"></i>{{ $admin->role->name ?? 'No Role Assigned' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header pb-0">
                                    <ul class="nav nav-tabs card-header-tabs profile-tabs" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#edit-profile" role="tab">Edit Profile</button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#change-password" role="tab">Change Password</button>
                                        </li>
                                    </ul>
                                </div>
                                <br>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Edit Profile Tab -->
                                        <div class="tab-pane fade show active" id="edit-profile" role="tabpanel">
                                            <!-- Display Validation Error Messages -->
                                            @if ($errors->hasAny(['name', 'email', 'phone', 'birthday', 'gender', 'img']))
                                                <div class="alert alert-danger">
                                                    <ul class="mb-0">
                                                        @foreach ($errors->only(['name', 'email', 'phone', 'birthday', 'gender', 'img']) as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')

                                                <div class="row">
                                                    <!-- Full Name -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="fullname">Full Name</label>
                                                        <input
                                                            name="name"
                                                            type="text"
                                                            class="form-control @error('name') is-invalid @enderror"
                                                            id="fullname"
                                                            placeholder="John Doe"
                                                            value="{{ old('name', $admin->name) }}"
                                                            required
                                                        />
                                                        @error('name')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <!-- Email -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="email">Email</label>
                                                        <input
                                                            type="email"
                                                            name="email"
                                                            id="email"
                                                            class="form-control @error('email') is-invalid @enderror"
                                                            placeholder="john.doe@example.com"
                                                            value="{{ old('email', $admin->email) }}"
                                                            required
                                                        />
                                                        @error('email')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Phone Number -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="phone">Phone No</label>
                                                        <input
                                                            type="text"
                                                            name="phone"
                                                            id="phone"
                                                            class="form-control @error('phone') is-invalid @enderror"
                                                            placeholder="+963-+971-1234567"
                                                            value="{{ old('phone', $admin->phone) }}"
                                                            required
                                                        />
                                                        @error('phone')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <!-- Birthday -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="birthday">Birthday</label>
                                                        <input
                                                            type="date"
                                                            name="birthday"
                                                            id="birthday"
                                                            class="form-control @error('birthday') is-invalid @enderror"
                                                            value="{{ old('birthday', $admin->birthday) }}"
                                                            required
                                                        />
                                                        @error('birthday')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Gender -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="gender">Gender</label>
                                                        <select
                                                            name="gender"
                                                            id="gender"
                                                            class="form-control @error('gender') is-invalid @enderror"
                                                            required
                                                        >
                                                            <option value="" disabled>Select Gender</option>
                                                            <option value="male" {{ old('gender', $admin->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                                            <option value="female" {{ old('gender', $admin->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                                        </select>
                                                        @error('gender')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <!-- Image Upload Input -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="profile-image">Update Profile Image</label>
                                                        <div class="input-group">
                                                            <input
                                                                type="file"
                                                                name="img"
                                                                id="profile-image"
                                                                class="form-control @error('img') is-invalid @enderror"
                                                                aria-label="Upload image"
                                                                accept="image/*"
                                                            />
                                                        </div>
                                                        <div class="form-text">Allowed formats: JPG, PNG, GIF. Leave empty to keep current image.</div>
                                                        @error('img')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Change Password Tab -->
                                        <div class="tab-pane fade" id="change-password" role="tabpanel">
                                            <!-- Display Validation Error Messages -->
                                            @if ($errors->hasAny(['current_password', 'password', 'password_confirmation']))
                                                <div class="alert alert-danger">
                                                    <ul class="mb-0">
                                                        @foreach ($errors->only(['current_password', 'password', 'password_confirmation']) as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <form method="post" action="{{ route('admin.profile.update.password') }}">
                                                @csrf
                                                @method('PUT')

                                                <div class="row">
                                                    <!-- Current Password -->
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label" for="current-password">Current Password</label>
                                                        <input
                                                            type="password"
                                                            name="current_password"
                                                            id="current-password"
                                                            class="form-control @error('current_password') is-invalid @enderror"
                                                            placeholder="Enter your current password"
                                                            required
                                                        />
                                                        @error('current_password')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- New Password -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="password">New Password</label>
                                                        <input
                                                            type="password"
                                                            name="password"
                                                            id="password"
                                                            class="form-control @error('password') is-invalid @enderror"
                                                            placeholder="Enter new password"
                                                            required
                                                        />
                                                        <div class="form-text">Password must be 8-20 characters and include: uppercase, lowercase, number, and special character.</div>
                                                        @error('password')
                                                        <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <!-- Confirm New Password -->
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label" for="password-confirmation">Confirm New Password</label>
                                                        <input
                                                            type="password"
                                                            name="password_confirmation"
                                                            id="password-confirmation"
                                                            class="form-control"
                                                            placeholder="Confirm new password"
                                                            required
                                                        />
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
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