<!-- Main Header -->
<header class="main-header">
    <!-- Logo -->
    <a href="{{ url('dashboard') }}" class="logo">
    	<img src="{{ asset($assetBasePath.Config::get('app_config.company_logo')) }}" alt="User Image" height="40" />
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <!-- The user image in the navbar-->
                        <!-- <img src="{{ asset($assetBasePath."/bower_components/adminLTE/dist/img/user2-160x160.jpg") }}" class="user-image" alt="User Image"/> -->
                        <!-- hidden-xs hides the username on small devices so only the image appears. -->
                        <i class="fa fa-user"></i>
                        <span class="hidden-xs">{{{ $userdata['name'] or "" }}}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- The user image in the menu -->
                        <li class="user-header">
                            <!-- <img src="{{ asset($assetBasePath."/bower_components/adminLTE/dist/img/user2-160x160.jpg") }}" class="img-circle" alt="User Image" /> -->
                            <p>
                                {{{ $userdata['empNo'] or "" }}} - {{{ $userdata['name'] or null }}}
                                <small>{{{ $roleDetails->role_name }}}</small>
                            </p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{{ url('/changePassword') }}" class="btn btn-default btn-flat">Change Password</a>
                            </div>
                            <div class="pull-right">
                                <a href="{{ url('/logout') }}" class="btn btn-default btn-flat">Sign out</a>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>