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

    <title>Admin Manage</title>

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

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Create Admin</h5>
                        </div>
                        <div class="card-body">

                            {{-- message Section --}}
                            @if (session('success_message_create'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success_message_create') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if (session('error_message_create'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error_message_create') }}
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

                            <form method="post" action="{{ route('admin.admin.store') }}" enctype="multipart/form-data">
                                @csrf

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
                                                value="{{ old('name') }}"
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
                                                value="{{ old('email') }}"
                                                required
                                            />
                                        </div>
                                        @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Password -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="basic-icon-default-password">Password</label>
                                        <div class="input-group input-group-merge">

                                            <input
                                                type="password"
                                                name="password"
                                                id="basic-icon-default-password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="Enter your password"
                                                required
                                            />
                                        </div>
                                        @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

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
                                                value="{{ old('phone') }}"
                                                required
                                            />
                                        </div>
                                        @error('phone')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
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
                                                value="{{ old('birthday') }}"
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
                                                <option value="" selected disabled>Select Status</option>
                                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                                <option value="" selected disabled>Select Gender</option>
                                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                            </select>
                                        </div>
                                        @error('gender')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="specialization">Roles</label>
                                        <div class="input-group input-group-merge">
                                            <select
                                                name="role_id"
                                                id="role"
                                                class="form-control @error('role_id') is-invalid @enderror"
                                                required
                                            >
                                                <option value="" selected disabled>Select Role</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                    <label class="form-label" for="basic-icon-default-image">Upload Image</label>
                                    <div class="input-group">

                                        <input
                                            type="file"
                                            name="img"
                                            id="basic-icon-default-image"
                                            class="form-control"
                                            aria-label="Upload image"
                                            accept="image/*"
                                            required
                                        />
                                    </div>
                                    <div class="form-text">Allowed formats: JPG, PNG, GIF</div>
                                </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Create</button>
                            </form>
                        </div>
                    </div>


                    <div class="card">
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

                        <h5 class="card-header">Admin Table</h5>

                        <!-- Filter Section -->
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <form method="GET" action="{{ route('admin.admin.index') }}" class="mb-4">
                                <div class="row g-3">

                                        <div class="col-md-3">
                                            <label for="search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="search" name="search"
                                                   placeholder="Search by name or email" value="{{ request('search') }}">
                                        </div>
                                    <div class="col-md-2">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" name="gender" id="gender">
                                            <option value="">All</option>
                                            <option value="1" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="0" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="">All</option>
                                            <option value="1" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ request('status') === 'inactive' ? 'selected' : '' }}>Not Active</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="from_date" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="to_date" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                                    </div>

                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                                        <a href="{{ route('admin.admin.index') }}" class="btn btn-secondary" style="height: 48px;padding-top: 12px">Reset</a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Export
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                                <li><a class="dropdown-item" href="{{ route('admin.admin.export', request()->all()) }}">Export to Excel</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- End Filter Section -->

                        <div class="table-responsive text-nowrap">
                            <table class="table">
                                <thead>
                                <tr class="text-nowrap">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Image</th>
                                    <th>Gender</th>
                                    <th>BirthDay</th>
                                    <th>Age</th>
                                    <th>Role</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Last Updated Date</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($admins as $index => $admin)
                                    <tr>
                                        <th scope="row">{{ ($admins->currentPage() - 1) * $admins->perPage() + $index + 1 }}</th>
                                        <td>{{$admin->name}}</td>
                                        <td>{{$admin->email}}</td>
                                        <td>{{$admin->phone}}</td>
                                        <td>
                                            @if ($admin->status === 'active')
                                                <div class="badge bg-success">Active</div>
                                            @else
                                                <div class="badge bg-danger">Not Active</div>
                                            @endif
                                        </td>
                                        <td>
                                            <img src="{{ asset('storage/' . $admin->img) }}"
                                                 style="width: 80px; height: 80px;" class="rounded">
                                        </td>
                                        <td>
                                            @if ($admin->gender === 'female')
                                                Female
                                            @else
                                                Male
                                            @endif
                                        </td>
                                        <td>{{$admin->birthday}}</td>
                                        <td>{{ \Carbon\Carbon::parse($admin->birthday)->age }}</td>
                                        <td>{{$admin->role->name ?? '-'}}</td>
                                        <td>{{$admin->createdBy->name ?? '-'}}</td>
                                        <td>{{$admin->created_at->format('Y-m-d')}}</td>
                                        <td>{{$admin->updated_at->format('Y-m-d')}}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{route('admin.admin.edit' , $admin->id)}}">
                                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                                    </a>
                                                    <form method="post" action="{{route('admin.admin.delete' , $admin->id)}}">
                                                        @csrf
                                                        @method('delete')
                                                        <button class="dropdown-item btn" type="submit" onclick="return confirm('Are you sure you want to delete this admin?')">
                                                            <i class="bx bx-trash me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center">No admins found</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <!-- Pagination Section -->
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    Showing {{ $admins->firstItem() ?? 0 }} to {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} entries
                                </div>

                                <div>
                                    @if ($admins->hasPages())
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination pagination-round pagination-primary mb-0">
                                                {{-- Previous Page Link --}}
                                                @if ($admins->onFirstPage())
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $admins->previousPageUrl() }}" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                @endif

                                                {{-- Pagination Elements --}}
                                                @foreach ($admins->getUrlRange(max(1, $admins->currentPage() - 2), min($admins->lastPage(), $admins->currentPage() + 2)) as $page => $url)
                                                    @if ($page == $admins->currentPage())
                                                        <li class="page-item active">
                                                            <a class="page-link" href="javascript:void(0);">{{ $page }}</a>
                                                        </li>
                                                    @else
                                                        <li class="page-item">
                                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                        </li>
                                                    @endif
                                                @endforeach

                                                {{-- Next Page Link --}}
                                                @if ($admins->hasMorePages())
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $admins->nextPageUrl() }}" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </nav>
                                    @endif
                                </div>

                                <div>
                                    <form method="GET" action="{{ route('admin.admin.index') }}" class="d-inline-flex align-items-center">
                                        @foreach(request()->except(['page', 'per_page']) as $key => $value)
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endforeach
                                        <label class="me-2">Show</label>
                                        <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                            @foreach([10, 25, 50, 100] as $perPage)
                                                <option value="{{ $perPage }}" {{ request('per_page', 10) == $perPage ? 'selected' : '' }}>
                                                    {{ $perPage }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="ms-2">entries</label>
                                    </form>
                                </div>
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
