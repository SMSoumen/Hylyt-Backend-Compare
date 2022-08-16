<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<?php
    $userData = Session::get(Config::get('app_config.user_sess_name'));
    $role = $userData[0]['role'];
    $roleDetails = App\Models\Role::where('role_id', '=', $role)->exists()->first();
    $userRightObjects = $roleDetails->right()->get();

    $userRightArr = array();
    foreach($userRightObjects as $userRightObject)
    {
        $moduleId = $userRightObject->module_id;
        $moduleName = App\Models\Module::where('module_id', '=', $moduleId)->exists()->first()->module_name;
        $userRightArr[$moduleName] = $userRightObject;
    }
?>
<?php
    $assetBasePath = Config::get('app_config.assetBasePath'); 
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title." | ".Config::get('app_config.company_name') }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.2 -->
    <link href="{{ asset($assetBasePath."/AdminLTE/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="{{ asset($assetBasePath."/dist/font-awesome/css/font-awesome.min.css") }}" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="{{ asset($assetBasePath."/dist/ionicons/css/ionicons.min.css") }}" rel="stylesheet" type="text/css" />
    
    <link href="{{ asset($assetBasePath."/dist/formvalidation_0_7_1/dist/css/formValidation.min.css")}}" rel="stylesheet" type="text/css" />
    <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
          page. However, you can choose any other skin. Make sure you
          apply the skin class to the body tag so the changes take effect.
    -->

    @if (isset($css))
		@for ($i = 0; $i < count($css); $i++)
		    <link href="{{ asset($assetBasePath.$css[$i])}}" rel="stylesheet" type="text/css" />
		@endfor
	@endif

    <!-- Theme style -->
    <link href='{{ asset($assetBasePath."/AdminLTE/dist/css/AdminLTE.min.css")}}' rel="stylesheet" type="text/css" />
    <link href='{{ asset($assetBasePath."/AdminLTE/dist/css/skins/skin-blue.min.css")}}' rel="stylesheet" type="text/css" />
    <link href='{{ asset($assetBasePath."/css/bootstrap_extend.css")}}' rel="stylesheet" type="text/css" />
    <link href='{{ asset($assetBasePath."/css/srac.css")}}' rel="stylesheet" type="text/css" />
	    
	<script>
        var siteurl = '{{ url("") }}';
        var statusChangeObjPlaceholder = '{{ Config::get("app_config.status_change_obj_placeholder") }}';
        var activationMessageTemplate = '{{ Config::get("app_config.activation_msg") }}';
        var inactivationMessageTemplate = '{{ Config::get("app_config.inactivation_msg") }}';
        var currPrec = '{{ Config::get("app_config.curr_prec") }}';
    </script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body class="skin-blue">
<div class="wrapper">

    <!-- Header -->
    @include('header')

    <!-- Sidebar -->
    @include('sidebar')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>
                <?php if(isset($page_icon)) echo $page_icon; ?>
                {{ $page_title or null }}
                <!--<small>{{ $page_description or null }}</small>-->
            </h1>
            <!-- You can dynamically generate breadcrumbs here -->
            <ol class="breadcrumb">
                <li><a href="{{ url('dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
                @if (isset($breadcrumbArr))
					@for ($i = 0; $i < count($breadcrumbArr); $i++)
						<li><a href="{{ url($breadcrumbLinkArr[$i]) }}">{{{ $breadcrumbArr[$i] }}}</a></li>          		
                	@endfor
				@endif
				@if (isset($pageName) && $pageName != "")
					<li class="active">{{{ $pageName }}}</li>
				@endif
            </ol>
        </section>

        <!-- Main content -->
        <section class="content">
            <!-- Your Page Content Here -->
            @yield('content')
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <!-- Footer -->
    @include('footer')

</div><!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.1.3 -->
<script src='{{ asset ($assetBasePath."/AdminLTE/plugins/jQuery/jQuery-2.2.0.min.js") }}'></script>
<!-- Bootstrap 3.3.2 JS -->
<script src='{{ asset ($assetBasePath."/AdminLTE/bootstrap/js/bootstrap.min.js") }}' type="text/javascript"></script>
<!-- AdminLTE App -->
<script src='{{ asset ($assetBasePath."/AdminLTE/dist/js/app.min.js") }}' type="text/javascript"></script>

<script src='{{ asset ($assetBasePath."/dist/formvalidation_0_7_1/dist/js/formValidation.min.js") }}' type="text/javascript"></script>
<script src='{{ asset ($assetBasePath."/dist/formvalidation_0_7_1/dist/js/framework/bootstrap.min.js") }}' type="text/javascript"></script>
<script src='{{ asset ($assetBasePath."/js/srac.js") }}' type="text/javascript"></script>
	
	@if (isset($js))
		@for ($i = 0; $i < count($js); $i++)
		    <script src="{{ asset ($assetBasePath.$js[$i]) }}" type="text/javascript"></script>
		@endfor
	@endif
	    
<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience -->
    @yield('int_scripts')
      
</body>
</html>