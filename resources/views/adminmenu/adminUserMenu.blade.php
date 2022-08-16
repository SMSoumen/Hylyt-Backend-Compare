<?php
    $roleDetails = App\Models\Org\CmsRole::where('role_id', '=', $roleId)->exists()->first();
    $userRightObjects = $roleDetails->right()->get();

    $userRightArr = array();
    foreach($userRightObjects as $userRightObject)
    {
        $moduleId = $userRightObject->module_id;
        $moduleName = App\Models\Org\CmsModule::where('module_id', '=', $moduleId)->exists()->first()->module_name;
        $userRightArr[$moduleName] = $userRightObject;
    }
?>
	<li class="header">MENU</li>  
	
    <!--@if($userRightArr[Config::get('app_config_module.mod_org_broadcast')]->module_view == 1)
        <li><a href="broadcast"><i class="fa fa-podcast"></i>&nbsp;<span>Broadcast</span></a></li>
    @endif -->
	
    @if($userRightArr[Config::get('app_config_module.mod_org_group')]->module_view == 1)
        <li><a href="group"><i class="fa fa-users"></i>&nbsp;<span>Group</span></a></li>
    @endif
	
    @if($userRightArr[Config::get('app_config_module.mod_org_employee')]->module_view == 1)
        <li><a href="appuser"><i class="fa fa-user"></i>&nbsp;<span>Appuser</span></a></li>
    @endif
	
    @if($userRightArr[Config::get('app_config_module.mod_org_system_tag')]->module_view == 1)
        <li><a href="systemTag"><i class="fa fa-tag"></i>&nbsp;<span>System Tag</span></a></li>
    @endif

	@if($userRightArr[Config::get('app_config_module.mod_org_notification')]->module_view == 1)
	    <li><a href="notification"><i class="fa fa-bullhorn"></i>&nbsp;<span>Notification</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_content_addition')]->module_view == 1)
	    <li><a href="contentAddition"><i class="fa fa-calendar-plus-o"></i>&nbsp;<span>Content Addition</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_video_conference')]->module_view == 1)
	    <li><a href="videoConference"><i class="fa fa-video-camera"></i>&nbsp;<span>Video Conference</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_template')]->module_view == 1)
	    <li><a href="template"><i class="fa fa-file-text-o"></i>&nbsp;<span>Template</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_backup')]->module_view == 1)
	    <li><a href="backupManagement"><i class="fa fa-archive"></i>&nbsp;<span>Backup & Restore</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_backup')]->module_view == 1)
	    <li><a href="administration"><i class="fa fa-user-secret"></i>&nbsp;<span>Administrator</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_backup')]->module_view == 1)
	    <li><a href="adminLog"><i class="fa fa-ellipsis-v"></i>&nbsp;<span>Administration Log</span></a></li>
	@endif

	@if($userRightArr[Config::get('app_config_module.mod_org_department')]->module_view == 1 || $userRightArr[Config::get('app_config_module.mod_org_designation')]->module_view == 1 )
	    <li class="treeview">
	        <a href="#"><i class="fa fa-cogs"></i>&nbsp;<span>General Masters</span> <i class="fa fa-angle-left pull-right"></i></a>
	        <ul class="treeview-menu">

	            @if($userRightArr[Config::get('app_config_module.mod_org_department')]->module_view == 1)
	                <li><a href="department"><i class="fa fa-sitemap"></i>&nbsp;<span>Department</span></a></li>
	            @endif

	            @if($userRightArr[Config::get('app_config_module.mod_org_designation')]->module_view == 1)
	                <li><a href="designation"><i class="fa fa-id-badge"></i>&nbsp;<span>Designation</span></a></li>
	            @endif

	            @if($userRightArr[Config::get('app_config_module.mod_org_badge')]->module_view == 1)
	                <li><a href="badge"><i class="fa fa-certificate"></i>&nbsp;<span>Badge</span></a></li>
	            @endif
	        </ul>
	    </li>
	@endif
       