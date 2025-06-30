<div id="scrollbar">
    <div class="container-fluid">
        <div id="two-column-menu"></div>

        <ul class="navbar-nav" id="navbar-nav">
            <li class="menu-title"><span data-key="t-dashboards">Dashboards</span></li>

            <li class="nav-item">
                <a class="nav-link menu-link {{ Request::routeIs(['admin.websites.index', 'admin.websites.create', 'admin.websites.show']) ? 'active' : '' }}"
                    href="#sidebarWebsite" data-bs-toggle="collapse" role="button"
                    aria-expanded="{{ Request::routeIs(['admin.websites.index', 'admin.websites.create', 'admin.websites.show']) ? 'true' : 'false' }}"
                    aria-controls="sidebarWebsite">
                    <i class="ri-global-line"></i> <span data-key="t-website">Website</span>
                </a>
                <div class="collapse menu-dropdown {{ Request::routeIs(['admin.websites.index', 'admin.websites.create', 'admin.websites.show']) ? 'show' : '' }}"
                    id="sidebarWebsite">
                    <ul class="nav nav-sm flex-column">
                        <li class="nav-item">
                            <a href="{{ route('admin.websites.index') }}"
                                class="nav-link {{ Request::routeIs('admin.websites.index') ? 'active' : '' }}"
                                data-key="t-website">
                                Danh sách
                            </a>
                            <a href="{{ route('admin.websites.create') }}"
                                class="nav-link {{ Request::routeIs('admin.websites.create') ? 'active' : '' }}"
                                data-key="t-website">
                                Thêm mới
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link {{ Request::routeIs(['admin.commissions.index', 'admin.commissions.create', 'admin.commissions.show']) ? 'active' : '' }}"
                    href="#sidebarCommission" data-bs-toggle="collapse" role="button"
                    aria-expanded="{{ Request::routeIs(['admin.commissions.index', 'admin.commissions.create', 'admin.commissions.show']) ? 'true' : 'false' }}"
                    aria-controls="sidebarCommission">
                    <i class="ri-task-line"></i> <span data-key="t-commisson">Nhiệm vụ</span>
                </a>
                <div class="collapse menu-dropdown {{ Request::routeIs(['admin.commissions.index', 'admin.commissions.create', 'admin.commissions.show']) ? 'show' : '' }}"
                    id="sidebarCommission">
                    <ul class="nav nav-sm flex-column">
                        <li class="nav-item">
                            <a href="{{ route('admin.commissions.index') }}"
                                class="nav-link {{ Request::routeIs('admin.commissions.index') ? 'active' : '' }}"
                                data-key="t-commisson">
                                Danh sách
                            </a>
                            <a href="{{ route('admin.commissions.create') }}"
                                class="nav-link {{ Request::routeIs('admin.commissions.create') ? 'active' : '' }}"
                                data-key="t-commisson">
                                Thêm mới
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link {{ Request::routeIs(['admin.users.index', 'admin.users.create', 'admin.users.show']) ? 'active' : '' }}"
                    href="#sidebarUser" data-bs-toggle="collapse" role="button"
                    aria-expanded="{{ Request::routeIs(['admin.users.index', 'admin.users.create', 'admin.users.show']) ? 'true' : 'false' }}"
                    aria-controls="sidebarUser">
                    <i class="ri-user-add-line"></i> <span data-key="t-user">Tài khoản</span>
                </a>
                <div class="collapse menu-dropdown {{ Request::routeIs(['admin.users.index', 'admin.users.create', 'admin.users.show']) ? 'show' : '' }}"
                    id="sidebarUser">
                    <ul class="nav nav-sm flex-column">
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}"
                                class="nav-link {{ Request::routeIs('admin.users.index') ? 'active' : '' }}"
                                data-key="t-user">
                                Danh sách
                            </a>
                            <a href="{{ route('admin.users.create') }}"
                                class="nav-link {{ Request::routeIs('admin.users.create') ? 'active' : '' }}"
                                data-key="t-user">
                                Thêm mới
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>