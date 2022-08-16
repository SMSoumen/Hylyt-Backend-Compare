<!DOCTYPE html>
<?php
$assetBasePath = Config::get('app_config.assetBasePath');
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>
			Login | {{ Config::get('app_config.company_name') }}
		</title>
		<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
		<!-- Bootstrap 3.3.2 -->
		<link href="{{ asset($assetBasePath.'/AdminLTE/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
		<!-- Font Awesome Icons -->
		<link href="{{ asset($assetBasePath.'/dist/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css" />
		<!-- Ionicons -->
		<!--<link href="{{ asset($assetBasePath."/dist/ionicons/css/ionicons.min.css") }}" rel="stylesheet" type="text/css" />-->
		<!-- Theme style -->
		<link href="{{ asset($assetBasePath.'/AdminLTE/dist/css/AdminLTE.min.css')}}" rel="stylesheet" type="text/css" />
		<!--
		AdminLTE Skins. We have chosen the skin-blue for this starter
		page. However, you can choose any other skin. Make sure you
		apply the skin class to the body tag so the changes take effect.
		-->
		<link href="{{ asset($assetBasePath.'/AdminLTE/dist/css/skins/skin-blue.min.css')}}" rel="stylesheet" type="text/css" />
	</head>
	<body class="hold-transition login-page">
		<div class="login-box">

			<!-- /.login-logo -->
			<div class="login-box-body">
				<div class="login-logo">
					<img src="{{ asset($assetBasePath.Config::get('app_config.company_logo')) }}" alt="User Image" height="100" />
				</div>
				<!-- <p class="login-box-msg">Sign In</p>-->
				<br/>
				{!! Form::open(['url' => '/authenticate', 'class' => 'form-horizontal', 'method' => 'POST']) !!}
					<div class="row"> 
						<div class="col-sm-12">
							<?php
							$errorStr = session('errorStr'); 
							?>
							@if (isset($errorStr))
						        <div class="alert alert-danger">
						        	{{ $errorStr }}
						        </div>
					        @endif
					    </div>
			        </div>

					<div class="form-group has-feedback @if ($errors->has('username')) has-error @endif">
						<input type="username" class="form-control" placeholder="Username" name="username" autocomplete="off">
						<span class="fa fa-user form-control-feedback">
						</span>
						@if ($errors->has('username')) <p class="help-block">{{ $errors->first('username') }}</p> @endif
					</div>												
					<div class="form-group has-feedback @if ($errors->has('password')) has-error @endif">
						<input type="password" class="form-control" placeholder="Password" name="password" autocomplete="off">
						<span class="fa fa-lock form-control-feedback">
						</span>
						@if ($errors->has('password')) <p class="help-block">{{ $errors->first('password') }}</p> @endif
					</div>
					<br/>
					<div class="row">
						<!-- /.col -->
						<div class="col-xs-offset-4 col-xs-4">
							{{ Form::button('<i class="fa fa-sign-in"></i>&nbsp;&nbsp;Sign In', ['type' => 'submit', 'class' => 'btn btn-primary btn-block btn-flat'] )  }}
						</div>
						<!-- /.col -->
					</div>
				{!! Form::close() !!}
			</div>
			<!-- /.login-box-body -->
		</div>
		<!-- /.login-box -->

		<!-- REQUIRED JS SCRIPTS -->

		<!-- jQuery 2.1.3 -->
		<script
			src="{{ asset ($assetBasePath.'/AdminLTE/plugins/jQuery/jQuery-2.2.0.min.js') }}">
		</script>
		<!-- Bootstrap 3.3.2 JS -->
		<script
			src="{{ asset ($assetBasePath.'/AdminLTE/bootstrap/js/bootstrap.min.js') }}" type="text/javascript">
		</script>
		<!-- AdminLTE App -->
		<script
			src="{{ asset ($assetBasePath.'/AdminLTE/dist/js/app.min.js') }}" type="text/javascript">
		</script>
	</body>
</html>