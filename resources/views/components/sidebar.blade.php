<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('admin.dashboard.index') }}" class="sidebar-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="site logo" class="light-logo">
            <img src="{{ asset('assets/images/logo-light.png') }}" alt="site logo" class="dark-logo">
            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="site logo" class="logo-icon">
        </a>
        
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="">
                <a href="{{ route('admin.dashboard.index') }}">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>

            @can('isSuperAdmin')
                <li class="">
                    <a href="{{ route('admin.user.index') }}">
                        <iconify-icon icon="solar:shield-user-broken" class="menu-icon"></iconify-icon>
                        <span>User</span>
                    </a>
                </li>
            @endcan

            <li class="">
                <a href="{{ route('admin.event_category.index') }}">
                    <iconify-icon icon="iconamoon:category-light" class="menu-icon"></iconify-icon>
                    <span>Event Category</span>
                </a>
            </li>

            <li class="">
                <a href="{{ route('admin.event.index') }}">
                    <iconify-icon icon="solar:calendar-add-broken" class="menu-icon"></iconify-icon>
                    <span>Event</span>
                </a>
            </li>

            <li class="dropdown my-2">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:trash-bin-minimalistic-broken" class="menu-icon"></iconify-icon>
                    <span>Trash</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('isSuperAdmin')
                        <li>
                            <a href="{{ route('admin.trash.users') }}"><i
                                    class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>
                                Trash Users</a>
                        </li>
                        <li>
                            <a href="{{ route('admin.trash.categories') }}"><i
                                    class="ri-circle-fill circle-icon text-danger-600 w-auto"></i>
                                Trash Event Category</a>
                        </li>
                        <li>
                            <a href="{{ route('admin.trash.events') }}"><i
                                    class="ri-circle-fill circle-icon text-warning-600 w-auto"></i>
                                Trash Events</a>
                        </li>
                    @endcan
                    <li>
                        <a href="{{ route('admin.trash.attendees.index') }}"><i
                                class="ri-circle-fill circle-icon text-success-600 w-auto"></i>
                            Trash Attendees</a>
                    </li>
                </ul>
            </li>

            {{-- <li class="dropdown my-2">
                <a href="javascript:void(0)">
                    <iconify-icon icon="ix:history-list" class="menu-icon"></iconify-icon>
                    <span>Logs</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.logs.users') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>
                            User Log</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.logs.qr') }}"><i class="ri-circle-fill circle-icon text-secondary-600 w-auto"></i>
                            Qr Code Log</a>
                    </li>
                </ul>
            </li> --}}
        </ul>
    </div>
</aside>
