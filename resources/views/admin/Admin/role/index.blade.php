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

    <title>Role Management</title>

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
        .permission-checkbox {
            margin-right: 10px;
            margin-bottom: 10px;
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
                            <h5 class="mb-0">Create Role</h5>
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

                            <form method="post" action="{{ route('admin.admin.role.store') }}">
                                @csrf

                                <div class="row">
                                    <!-- Role Name -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="name">Role Name</label>
                                        <input
                                            name="name"
                                            type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            id="name"
                                            placeholder="Enter role name"
                                            value="{{ old('name') }}"
                                            required
                                        />
                                        @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Role Type -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="type">Role Type</label>
                                        <select
                                            name="type"
                                            id="type"
                                            class="form-control @error('type') is-invalid @enderror"
                                            required
                                        >
                                            <option value="" selected disabled>Select Role Type</option>
                                            <option value="admin" {{ old('type') == 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="employee" {{ old('type') == 'employee' ? 'selected' : '' }}>Employee</option>
                                        </select>
                                        @error('type')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Description -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="description">Description</label>
                                        <textarea
                                            name="description"
                                            id="description"
                                            class="form-control @error('description') is-invalid @enderror"
                                            placeholder="Enter role description"
                                            rows="3"
                                        >{{ old('description') }}</textarea>
                                        @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="is_active">Status</label>
                                        <select
                                            name="is_active"
                                            id="is_active"
                                            class="form-control @error('is_active') is-invalid @enderror"
                                            required
                                        >
                                            <option value="" selected disabled>Select Status</option>
                                            <option value="1" {{ old('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('is_active')
                                        <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Permissions -->
                                <div class="mb-3">
                                    <label class="form-label">Permissions</label>
                                    <div class="row">
                                        <div class="col-12 mb-2 permission-container">
                                            <!-- Admin permissions section -->
                                            <div id="admin-permissions" class="row" style="display: none;">
                                                <div class="col-12 mb-2">
                                                    <div class="alert alert-info">
                                                        Showing admin permissions
                                                    </div>
                                                    <div class="mb-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary select-all-admin">Select All</button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-admin">Deselect All</button>
                                                    </div>
                                                </div>
                                                @foreach($adminPermissions as $permission)
                                                    <div class="col-md-3 permission-checkbox">
                                                        <div class="form-check">
                                                            <input 
                                                                class="form-check-input admin-permission" 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission-{{ $permission->id }}"
                                                                {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                            >
                                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Employee permissions section -->
                                            <div id="employee-permissions" class="row" style="display: none;">
                                                <div class="col-12 mb-2">
                                                    <div class="alert alert-info">
                                                        Showing employee permissions
                                                    </div>
                                                    <div class="mb-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary select-all-employee">Select All</button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-employee">Deselect All</button>
                                                    </div>
                                                </div>
                                                @foreach($employeePermissions as $permission)
                                                    <div class="col-md-3 permission-checkbox">
                                                        <div class="form-check">
                                                            <input 
                                                                class="form-check-input employee-permission" 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission-{{ $permission->id }}"
                                                                {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                            >
                                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Default message when no type is selected -->
                                            <div id="no-type-selected" class="alert alert-warning">
                                                Please select a role type to view available permissions
                                            </div>
                                        </div>
                                    </div>
                                    @error('permissions')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">Create Role</button>
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

                        <h5 class="card-header">Roles Table</h5>

                        <!-- Filter Section -->
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <form method="GET" action="{{ route('admin.admin.role.index') }}" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                               placeholder="Search by name or description" value="{{ request('search') }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="">All</option>
                                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="type" class="form-label">Type</label>
                                        <input type="text" class="form-control" id="type" name="type"
                                               placeholder="Filter by type" value="{{ request('type') }}">
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
                                        <a href="{{ route('admin.admin.role.index') }}" class="btn btn-secondary" style="height: 48px;padding-top: 12px">Reset</a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Export
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                                <li><a class="dropdown-item" href="{{ route('admin.admin.role.export', request()->all()) }}">Export to Excel</a></li>
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
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Permissions</th>
                                    <th>Created Date</th>
                                    <th>Last Updated Date</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($roles as $index => $role)
                                    <tr>
                                        <th scope="row">{{ ($roles->currentPage() - 1) * $roles->perPage() + $index + 1 }}</th>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->slug }}</td>
                                        <td>{{ $role->description ?? '-' }}</td>
                                        <td>
                                            @if ($role->is_active == 1)
                                                <div class="badge bg-success">Active</div>
                                            @else
                                                <div class="badge bg-danger">Inactive</div>
                                            @endif
                                        </td>
                                        <td>{{ $role->type }}</td>
                                        <td>
                                            @php
                                                $rolePermissions = \App\Models\RolePermission::where('role_id', $role->id)
                                                    ->where('is_active', 1)
                                                    ->with('permission')
                                                    ->get();
                                                $permissionCount = $rolePermissions->count();
                                            @endphp
                                            <span class="badge bg-info">{{ $permissionCount }} permission(s)</span>
                                            @if($permissionCount > 0)
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#permissionModal{{ $role->id }}">
                                                    View
                                                </button>
                                                
                                                <!-- Modal for Permissions -->
                                                <div class="modal fade" id="permissionModal{{ $role->id }}" tabindex="-1" aria-labelledby="permissionModalLabel{{ $role->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="permissionModalLabel{{ $role->id }}">Permissions for {{ $role->name }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <ul class="list-group">
                                                                    @foreach($rolePermissions as $rp)
                                                                        <li class="list-group-item">{{ $rp->permission->name }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $role->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $role->updated_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('admin.admin.role.edit', $role->id) }}">
                                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                                    </a>
                                                    <form method="post" action="{{ route('admin.admin.role.delete', $role->id) }}">
                                                        @csrf
                                                        @method('delete')
                                                        <button class="dropdown-item btn" type="submit" onclick="return confirm('Are you sure you want to delete this role?')">
                                                            <i class="bx bx-trash me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No roles found</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Section -->
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    Showing {{ $roles->firstItem() ?? 0 }} to {{ $roles->lastItem() ?? 0 }} of {{ $roles->total() }} entries
                                </div>

                                <div>
                                    @if ($roles->hasPages())
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination pagination-round pagination-primary mb-0">
                                                {{-- Previous Page Link --}}
                                                @if ($roles->onFirstPage())
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $roles->previousPageUrl() }}" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                @endif

                                                {{-- Pagination Elements --}}
                                                @foreach ($roles->getUrlRange(max(1, $roles->currentPage() - 2), min($roles->lastPage(), $roles->currentPage() + 2)) as $page => $url)
                                                    @if ($page == $roles->currentPage())
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
                                                @if ($roles->hasMorePages())
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $roles->nextPageUrl() }}" aria-label="Next">
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
                                    <form method="GET" action="{{ route('admin.admin.role.index') }}" class="d-inline-flex align-items-center">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleTypeSelect = document.getElementById('type');
        const adminPermissionsContainer = document.getElementById('admin-permissions');
        const employeePermissionsContainer = document.getElementById('employee-permissions');
        const noTypeSelectedMessage = document.getElementById('no-type-selected');
        
        // Select/Deselect all buttons for admin permissions
        const selectAllAdminBtn = document.querySelector('.select-all-admin');
        const deselectAllAdminBtn = document.querySelector('.deselect-all-admin');
        
        // Select/Deselect all buttons for employee permissions
        const selectAllEmployeeBtn = document.querySelector('.select-all-employee');
        const deselectAllEmployeeBtn = document.querySelector('.deselect-all-employee');
        
        // Add event listeners for select/deselect buttons
        if (selectAllAdminBtn) {
            selectAllAdminBtn.addEventListener('click', function() {
                document.querySelectorAll('.admin-permission').forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            });
        }
        
        if (deselectAllAdminBtn) {
            deselectAllAdminBtn.addEventListener('click', function() {
                document.querySelectorAll('.admin-permission').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            });
        }
        
        if (selectAllEmployeeBtn) {
            selectAllEmployeeBtn.addEventListener('click', function() {
                document.querySelectorAll('.employee-permission').forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            });
        }
        
        if (deselectAllEmployeeBtn) {
            deselectAllEmployeeBtn.addEventListener('click', function() {
                document.querySelectorAll('.employee-permission').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            });
        }
        
        // Function to display appropriate permissions based on role type
        function updatePermissionsDisplay() {
            if (!roleTypeSelect) return;
            
            const selectedType = roleTypeSelect.value;
            
            // Hide all permission containers first
            if (adminPermissionsContainer) adminPermissionsContainer.style.display = 'none';
            if (employeePermissionsContainer) employeePermissionsContainer.style.display = 'none';
            if (noTypeSelectedMessage) noTypeSelectedMessage.style.display = 'block';
            
            // Show the appropriate permissions based on selection
            if (selectedType === 'admin') {
                if (adminPermissionsContainer) adminPermissionsContainer.style.display = 'flex';
                if (noTypeSelectedMessage) noTypeSelectedMessage.style.display = 'none';
            } else if (selectedType === 'employee') {
                if (employeePermissionsContainer) employeePermissionsContainer.style.display = 'flex';
                if (noTypeSelectedMessage) noTypeSelectedMessage.style.display = 'none';
            }
        }
        
        // Add event listener to role type select
        if (roleTypeSelect) {
            roleTypeSelect.addEventListener('change', updatePermissionsDisplay);
            
            // Also run on page load to handle pre-selected values (e.g., from validation errors)
            updatePermissionsDisplay();
        }
    });
</script>

</body>
</html> 