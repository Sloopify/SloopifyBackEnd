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

    <title>Edit Role</title>

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
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Edit Role</h5>
                                    <a href="{{ route('admin.admin.role.index') }}" class="btn btn-secondary">Back</a>
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

                                    <form method="post" action="{{ route('admin.admin.role.update', $role->id) }}">
                                        @csrf
                                        @method('PUT')

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
                                                    value="{{ old('name', $role->name) }}"
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
                                                    <option value="admin" {{ old('type', $role->type) == 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="employee" {{ old('type', $role->type) == 'employee' ? 'selected' : '' }}>Employee</option>
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
                                                >{{ old('description', $role->description) }}</textarea>
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
                                                    <option value="" disabled>Select Status</option>
                                                    <option value="1" {{ old('is_active', $role->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                                    <option value="0" {{ old('is_active', $role->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
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
                                                                        {{ in_array($permission->id, old('permissions', $assignedPermissions)) ? 'checked' : '' }}
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
                                                                        {{ in_array($permission->id, old('permissions', $assignedPermissions)) ? 'checked' : '' }}
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

                                        <button type="submit" class="btn btn-primary">Update Role</button>
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