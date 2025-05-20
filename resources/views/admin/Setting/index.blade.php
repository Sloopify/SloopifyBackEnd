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

    <title>Settings</title>

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
                    <h4 class="fw-bold py-3 mb-4">
                        <span class="text-muted fw-light">Settings /</span> Manage Settings
                    </h4>

                    <!-- Alert messages -->
                    @if (session('success_message'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if (session('error_message'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        {{ session('error_message') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <!-- Settings Tabs -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                        @if(isset($groupedSettings['general']) && count($groupedSettings['general']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['onboarding']) && count($groupedSettings['onboarding']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="onboarding-tab" data-bs-toggle="tab" href="#onboarding" role="tab" aria-controls="onboarding" aria-selected="false">Onboarding</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['authentication']) && count($groupedSettings['authentication']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="authentication-tab" data-bs-toggle="tab" href="#authentication" role="tab" aria-controls="authentication" aria-selected="false">Authentication</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['normal_post']) && count($groupedSettings['normal_post']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="normal-post-tab" data-bs-toggle="tab" href="#normal-post" role="tab" aria-controls="normal-post" aria-selected="false">Normal Post</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['post_live']) && count($groupedSettings['post_live']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="post-live-tab" data-bs-toggle="tab" href="#post-live" role="tab" aria-controls="post-live" aria-selected="false">Post Live</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['post_audience']) && count($groupedSettings['post_audience']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="post-audience-tab" data-bs-toggle="tab" href="#post-audience" role="tab" aria-controls="post-audience" aria-selected="false">Post Audience</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['story']) && count($groupedSettings['story']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="story-tab" data-bs-toggle="tab" href="#story" role="tab" aria-controls="story" aria-selected="false">Story</a>
                                        </li>
                                        @endif
                                        
                                        @if(isset($groupedSettings['miscellaneous']) && count($groupedSettings['miscellaneous']) > 0)
                                        <li class="nav-item">
                                            <a class="nav-link" id="miscellaneous-tab" data-bs-toggle="tab" href="#miscellaneous" role="tab" aria-controls="miscellaneous" aria-selected="false">Miscellaneous</a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>

                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- General Settings -->
                                        @if(isset($groupedSettings['general']) && count($groupedSettings['general']) > 0)
                                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['general'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Onboarding Settings -->
                                        @if(isset($groupedSettings['onboarding']) && count($groupedSettings['onboarding']) > 0)
                                        <div class="tab-pane fade" id="onboarding" role="tabpanel" aria-labelledby="onboarding-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['onboarding'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Authentication Settings -->
                                        @if(isset($groupedSettings['authentication']) && count($groupedSettings['authentication']) > 0)
                                        <div class="tab-pane fade" id="authentication" role="tabpanel" aria-labelledby="authentication-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['authentication'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Normal Post Settings -->
                                        @if(isset($groupedSettings['normal_post']) && count($groupedSettings['normal_post']) > 0)
                                        <div class="tab-pane fade" id="normal-post" role="tabpanel" aria-labelledby="normal-post-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['normal_post'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Post Live Settings -->
                                        @if(isset($groupedSettings['post_live']) && count($groupedSettings['post_live']) > 0)
                                        <div class="tab-pane fade" id="post-live" role="tabpanel" aria-labelledby="post-live-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['post_live'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Post Audience Settings -->
                                        @if(isset($groupedSettings['post_audience']) && count($groupedSettings['post_audience']) > 0)
                                        <div class="tab-pane fade" id="post-audience" role="tabpanel" aria-labelledby="post-audience-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['post_audience'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Story Settings -->
                                        @if(isset($groupedSettings['story']) && count($groupedSettings['story']) > 0)
                                        <div class="tab-pane fade" id="story" role="tabpanel" aria-labelledby="story-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['story'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <!-- Miscellaneous Settings -->
                                        @if(isset($groupedSettings['miscellaneous']) && count($groupedSettings['miscellaneous']) > 0)
                                        <div class="tab-pane fade" id="miscellaneous" role="tabpanel" aria-labelledby="miscellaneous-tab">
                                            <div class="row">
                                                @foreach($groupedSettings['miscellaneous'] as $setting)
                                                <div class="col-md-6 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">{{ $setting->description }}</h5>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-description" 
                                                                data-bs-toggle="modal" data-bs-target="#editDescriptionModal" 
                                                                data-key="{{ $setting->key }}" 
                                                                data-description="{{ $setting->description }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </button>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Key</label>
                                                                <input type="text" class="form-control" value="{{ $setting->key }}" readonly />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Value</label>
                                                                
                                                                @if($setting->value == '0' || $setting->value == '1')
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input toggle-setting" type="checkbox" 
                                                                        id="setting_{{ $setting->key }}" 
                                                                        data-key="{{ $setting->key }}" 
                                                                        {{ $setting->value == '1' ? 'checked' : '' }}/>
                                                                    <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                                        {{ $setting->value == '1' ? 'Enabled' : 'Disabled' }}
                                                                    </label>
                                                                </div>
                                                                @else
                                                                <input type="text" class="form-control setting-value" 
                                                                    id="setting_value_{{ $setting->key }}" 
                                                                    value="{{ $setting->value }}" 
                                                                    data-key="{{ $setting->key }}" />
                                                                <button class="btn btn-sm btn-primary mt-2 update-setting-value">
                                                                    Update
                                                                </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- / Content -->

                <!-- Footer -->
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

<!-- Edit Description Modal -->
<div class="modal fade" id="editDescriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Description</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="edit-description-input" class="form-label">Description</label>
                        <input type="text" id="edit-description-input" class="form-control" />
                        <input type="hidden" id="edit-description-key" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-description">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Form for updating text values -->
<form id="update-setting-form" method="post" action="{{ route('admin.settings.update') }}">
    @csrf
    <div id="setting-values-container"></div>
</form>

@include('layouts.Admin.LinkJS')

<script>
    $(document).ready(function() {
        // Toggle setting value (for boolean settings)
        $('.toggle-setting').change(function() {
            const key = $(this).data('key');
            const label = $(this).next('label');
            
            $.ajax({
                url: "{{ route('admin.settings.toggle.value') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    key: key
                },
                success: function(response) {
                    if (response.success) {
                        if (response.value === '1') {
                            label.text('Enabled');
                        } else {
                            label.text('Disabled');
                        }
                        
                        // Show success toast
                        toastr.success('Setting updated successfully');
                    } else {
                        // Show error toast
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to update setting');
                }
            });
        });
        
        // Edit description modal
        $('.edit-description').click(function() {
            const key = $(this).data('key');
            const description = $(this).data('description');
            
            $('#edit-description-key').val(key);
            $('#edit-description-input').val(description);
        });
        
        // Save description
        $('#save-description').click(function() {
            const key = $('#edit-description-key').val();
            const description = $('#edit-description-input').val();
            
            $.ajax({
                url: "{{ route('admin.settings.update.description') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    key: key,
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#editDescriptionModal').modal('hide');
                        
                        // Update description in the card
                        $(`button[data-key="${key}"]`).closest('.card-header').find('h5').text(description);
                        $(`button[data-key="${key}"]`).data('description', description);
                        
                        // Show success toast
                        toastr.success('Description updated successfully');
                    } else {
                        // Show error toast
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to update description');
                }
            });
        });
        
        // Update text setting value
        $('.update-setting-value').click(function() {
            const key = $(this).prev('.setting-value').data('key');
            const value = $(this).prev('.setting-value').val();
            
            // Clear previous values
            $('#setting-values-container').empty();
            
            // Add the setting to the form
            $('#setting-values-container').append(`
                <input type="hidden" name="settings[0][key]" value="${key}">
                <input type="hidden" name="settings[0][value]" value="${value}">
            `);
            
            // Submit the form
            $('#update-setting-form').submit();
        });
    });
</script>

</body>
</html> 