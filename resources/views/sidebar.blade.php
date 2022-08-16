<!-- Left side column. contains the sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        <!--<div class="user-panel">
            <div class="pull-left image">
                <img src="{{ asset($assetBasePath."/bower_components/adminLTE/dist/img/user2-160x160.jpg") }}" class="img-circle" alt="User Image" />
            </div>
            <div class="pull-left info">
                <p>Alexander Pierce</p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>-->

        <!-- search form (Optional) -->
        <!-- <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
                <span class="input-group-btn">
                  <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
                </span>
            </div>
        </form> -->
        <!-- /.search form -->

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">MENU</li>
            
            @if($userRightArr[Config::get('app_config_module.mod_appuser')]->module_view == 1)
                <li><a href="{{ route('appuserServer') }}"><i class="fa fa-user"></i>&nbsp;<span>App User</span></a></li>
            @endif

            @if($userRightArr[Config::get('app_config_module.mod_appuser')]->module_view == 1)
                <li><a href="{{ route('deletedAppuserServer') }}"><i class="fa fa-user"></i>&nbsp;<span>Deleted App User</span></a></li>
            @endif
            
            @if($userRightArr[Config::get('app_config_module.mod_notification')]->module_view == 1)
                <li><a href="{{ route('notification') }}"><i class="fa fa-bullhorn"></i>&nbsp;<span>Notification</span></a></li>
            @endif
            
            @if($userRightArr[Config::get('app_config_module.mod_content_addition')]->module_view == 1)
                <li><a href="{{ route('contentAddition') }}"><i class="fa fa-calendar-plus-o"></i>&nbsp;<span>Content Addition</span></a></li>
            @endif
            
            @if($userRightArr[Config::get('app_config_module.mod_thought_tip')]->module_view == 1)
                <li><a href="{{ route('thoughtTip') }}"><i class="fa fa-lightbulb-o"></i>&nbsp;<span>Thought/Tip</span></a></li>
            @endif

            @if($userRightArr[Config::get('app_config_module.mod_employee')]->module_view == 1 || $userRightArr[Config::get('app_config_module.mod_department')]->module_view == 1 )
                <li class="treeview">
                    <a href="#"><i class="fa fa-cogs"></i>&nbsp;<span>General Masters</span> <i class="fa fa-angle-left pull-right"></i></a>
                    <ul class="treeview-menu">
                        @if($userRightArr[Config::get('app_config_module.mod_employee')]->module_view == 1)
                            <li><a href="{{ route('employee') }}"><i class="fa fa-users"></i>&nbsp;<span>Employee</span></a></li>
                        @endif

                        @if($userRightArr[Config::get('app_config_module.mod_department')]->module_view == 1)
                            <li><a href="{{ route('department') }}"><i class="fa fa-sitemap"></i>&nbsp;<span>Department</span></a></li>
                        @endif
                    </ul>
                </li>
            @endif

            <!-- @if($userRightArr[Config::get('app_config_module.mod_role')]->module_view == 1 )
                <li class="treeview">
                    <a href="#"><i class="fa fa-graduation-cap"></i>&nbsp;<span>Administration</span> <i class="fa fa-angle-left pull-right"></i></a>
                    <ul class="treeview-menu">
                        @if($userRightArr[Config::get('app_config_module.mod_role')]->module_view == 1)
                            <li><a href="{{ route('role') }}"><i class="fa fa-check-square-o"></i>&nbsp;<span>Role</span></a></li>
                        @endif
                    </ul>
                </li>
            @endif -->
        </ul><!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>