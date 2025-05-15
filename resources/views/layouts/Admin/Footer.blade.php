<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
        <div class="mb-2 mb-md-0">
            ©
            <script>
                document.write(new Date().getFullYear());
            </script>
            Made By
            <a href="https://linktree.api-puzzle.rf.gd" target="_blank" class="footer-link fw-bolder">API Puzzle</a>
        </div>
    </div>
</footer>



<ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item active">
            <a href="{{route('admin.dashboard')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Web</span></li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-briefcase"></i>

                <div data-i18n="Layouts">Services</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="{{route('admin.service.index')}}" class="menu-link">
                        <div data-i18n="Without menu">Service Manage</div>
                    </a>
                </li>

            </ul>
        </li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-shopping-bag"></i>



                <div data-i18n="Layouts">Order Manage</div>
            </a>

            <ul class="menu-sub">
        
        <li class="menu-item {{
             (request()->routeIs('admin.order.index') &&
             (request()->route('status') == 'all' || request()->route('status') === null)) ? 'active' : ''
              }}">
                    <a href="{{ route('admin.order.index', 'all') }}" class="menu-link">
                        <div data-i18n="All Requests">All Orders</div>
                    </a>
                </li>
                @php
                    $statuses = ['pending', 'completed', 'canceled'];
                @endphp
                @foreach($statuses as $status)
                    <li class="menu-item {{
            request()->routeIs('admin.order.index') &&
            request()->route('status') == $status ? 'active' : ''
             }}">
                        <a href="{{ route('admin.order.index', $status) }}" class="menu-link">
                            <div data-i18n="{{ ucwords(str_replace('_', ' ', $status)) }}">
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Payment</span></li>

      <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-credit-card"></i>


                <div data-i18n="Layouts">Payment Request</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item {{
           (request()->routeIs('admin.customer.payment.request.index') &&
            (request()->route('status') == 'all' || request()->route('status') === null)) ? 'active' : ''
             }}">
                    <a href="{{ route('admin.customer.payment.request.index', 'all') }}" class="menu-link">
                        <div data-i18n="All Requests">All Requests</div>
                    </a>
                </li>
                @php
                    $statuses = ['pending', 'approved', 'canceled_without_returned', 'canceled_with_returned'];
                @endphp
                @foreach($statuses as $status)
                    <li class="menu-item {{
            request()->routeIs('admin.customer.payment.request.index') &&
            request()->route('status') == $status ? 'active' : ''
            }}">
                        <a href="{{ route('admin.customer.payment.request.index', $status) }}" class="menu-link">
                            <div data-i18n="{{ ucwords(str_replace('_', ' ', $status)) }}">
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">System</span></li>
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>

                <div data-i18n="Layouts">Employee</div>
            </a>

            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="{{route('admin.employee.index')}}" class="menu-link">
                        <div data-i18n="Without menu">Employee Manage</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{route('admin.employee.role.index')}}" class="menu-link">
                        <div data-i18n="Without menu">Role & Permission</div>
                    </a>
                </li>

            </ul>
        </li>


        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>

                <div data-i18n="Account Settings">Customers</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="{{route('admin.customer.index')}}" class="menu-link">
                        <div data-i18n="Account">Customer Manage</div>
                    </a>
                </li>
            </ul>
        </li>


        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cog"></i>

                <div data-i18n="Account Settings">Settings</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="{{route('admin.setting.index')}}" class="menu-link">
                        <div data-i18n="Account">Setting Manage</div>
                    </a>
                </li>
            </ul>
        </li>

    </ul>