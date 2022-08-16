<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<?php
    $assetBasePath = Config::get('app_config.assetBasePath'); 
?>    
@if (isset($css))
	@for ($i = 0; $i < count($css); $i++)
	    <link href="{{ asset($assetBasePath.$css[$i])}}" rel="stylesheet" type="text/css" />
	@endfor
@endif
    
<script>
    var siteurl = '{{ url("") }}';
    var statusChangeObjPlaceholder = '{{ Config::get("app_config.status_change_obj_placeholder") }}';
    var activationMessageTemplate = '{{ Config::get("app_config.activation_msg") }}';
    var inactivationMessageTemplate = '{{ Config::get("app_config.inactivation_msg") }}';
    var currPrec = '{{ Config::get("app_config.curr_prec") }}';
</script>

@yield('content')

@if (isset($js))
	@for ($i = 0; $i < count($js); $i++)
	    <script src="{{ asset ($assetBasePath.$js[$i]) }}" type="text/javascript"></script>
	@endfor
@endif
    
@yield('int_scripts')