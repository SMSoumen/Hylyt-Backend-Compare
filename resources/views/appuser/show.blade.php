@extends('admin_template')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Box -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    {{ $page_description or null }}
                    <?php 
                    if($modulePermissions->module_edit == 1) 
                    {
                    	if(isset($userConstant))
                    	{?>
							 <button class="btn btn-xs btn-warning" onclick="loadModifyUserQuotaModal({{ $userConstant->appuser_constant_id }});">
	                            <i class="fa fa-database"></i>&nbsp;&nbsp;Modify Quota
	                        </button>
	                        &nbsp;
						<?php 
						}
                        $isActive = $user->is_active;
                        $moduleName = $page_title;
                        $id = $user->appuser_id;
                        
                        $btnClass = ($isActive==1) ? Config::get('app_config.active_btn_class') : Config::get('app_config.inactive_btn_class');
						$iconClass = ($isActive==1) ? Config::get('app_config.active_btn_icon_class') : Config::get('app_config.inactive_btn_icon_class');
						$event = 'changeStatus("'.$moduleName.'",'.$id.','.$isActive.');';						
						$statusText =($isActive==1) ? Config::get('app_config.active_btn_text') : Config::get('app_config.inactive_btn_text');
						
                        $isPremium = $user->is_premium; 
						
                        $premBtnClass = ($isPremium==1) ? Config::get('app_config.premium_active_btn_class') : Config::get('app_config.premium_inactive_btn_class');
						$premIconClass = ($isPremium==1) ? Config::get('app_config.premium_active_btn_icon_class') : Config::get('app_config.premium_inactive_btn_icon_class');
						$premEvent = 'toggleAppuserPremiumStatus('.$id.','.$isPremium.');';						
						$premStatusText = ($isPremium==1) ? Config::get('app_config.premium_active_btn_text') : Config::get('app_config.premium_inactive_btn_text');
                        ?>
						<button class="btn btn-xs {{ $btnClass }}" onclick="{{ $event }}">
                            <i class="fa {{ $iconClass }}"></i>&nbsp;&nbsp;{{ $statusText }}
                        </button>  
                        &nbsp;                        
						<button class="btn btn-xs {{ $premBtnClass }}" onclick="{{ $premEvent }}">
                            <i class="fa {{ $premIconClass }}"></i>&nbsp;&nbsp;{{ $premStatusText }}
                        </button>       
                        @if($isPremium==1 && $isActive==1)
                            &nbsp;                        
                            <button class="btn btn-xs btn-orange" onclick="loadModifyPremiumExpirationDateModal({{ $id }})">
                                <i class="fa fa-calendar"></i>&nbsp;&nbsp;Modify Premium Expiration Date
                            </button>
                        @endif              
                    <?php
                    }?>
                </h3>
            </div>
            <div class="box-body">
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('name', 'Name', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->fullname != "" ? $user->fullname : "-" }}
                            </div>
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('email', 'Email', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->email != "" ? $user->email : "-" }}
                            </div>
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('contact', 'Contact', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->contact != "" ? $user->contact : "-" }}
                            </div>
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('gender', 'Gender', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->gender != "" ? $user->gender : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('last_synced', 'Registered On', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->created_at != "" && $user->created_at != '0000-00-00' ? date(Config::get('app_config.datetime_disp_format_without_second'), strtotime($user->created_at)) : "-" }}
                            </div>
                        </div>
                    </div> 
                    
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('app_registered', 'Registration Type', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->is_app_registered != 1 ? "Facebook" : "Email" }}
                            </div>
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('city', 'City', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->city != "" ? $user->city : "-" }}
                            </div>
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('country', 'Country', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->country != "" ? $user->country : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('ver_status', 'Verification Status', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->is_verified == 1 ? "Verified" : "Pending" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('login_status', 'Login Status', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ $user->is_logged_in == 1 ? "Active" : "Inactive" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('timezone', 'Timezone', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ (isset($userConstant) && $userConstant->timezone_id != "") ? $userConstant->timezone_id : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('alloted_space', 'Allotted Space', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6" id="divAllotSpace">
                                {{ (isset($userConstant) && $userConstant->attachment_kb_allotted != "") ? ceil($userConstant->attachment_kb_allotted/1024)." MB" : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('available_space', 'Available Space', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6" id="divAvlSpace">
                                {{ (isset($userConstant) && $userConstant->attachment_kb_available != "") ? ceil($userConstant->attachment_kb_available/1024)." MB" : "-" }}
                            </div>
                        </div>
                    </div> 

                    <div class="form-group">
                        <div class="row">
                            {!! Form::label('last_synced', 'Last Synced On', ['class' => 'col-sm-3 control-label']) !!}
                            <div class="col-sm-6">
                                {{ (isset($user->last_sync_ts) && $user->last_sync_ts != "" && $user->last_sync_ts != '0000-00-00 00:00:00') ? date(Config::get('app_config.datetime_disp_format_without_second'), strtotime($user->last_sync_ts)) : "-" }}
                            </div>
                        </div>
                    </div> 

                    @if($isPremium == 1)
                        <div class="form-group">
                            <div class="row">
                                {!! Form::label('last_synced', 'Premium Activation Date', ['class' => 'col-sm-3 control-label']) !!}
                                <div class="col-sm-6">
                                    {{ (isset($user->premium_activation_date) && $user->premium_activation_date != "" && $user->premium_activation_date != '0000-00-00') ? date(Config::get('app_config.date_disp_format'), strtotime($user->premium_activation_date)) : "-" }}
                                </div>
                            </div>
                        </div> 

                        <div class="form-group">
                            <div class="row">
                                {!! Form::label('last_synced', 'Premium Expiration Date', ['class' => 'col-sm-3 control-label']) !!}
                                <div class="col-sm-6" id="divPremiumExpirationDate">
                                    {{ (isset($user->premium_expiration_date) && $user->premium_expiration_date != "" && $user->premium_expiration_date != '0000-00-00') ? date(Config::get('app_config.date_disp_format'), strtotime($user->premium_expiration_date)) : "-" }}
                                </div>
                            </div>
                        </div> 
                    @endif
				
					{{ Form::open(array('url' => route('appuserServer.changeStatus'), 'id' => 'frmStatusChange')) }}
						{!! Form::hidden('userId', 0, ['id' => 'statusId']) !!}
						{!! Form::hidden('statusActive', 0, ['id' => 'statusActive']) !!}
					{{ Form::close() }}
				
					{{ Form::open(array('url' => route('appuserServer.changePremiumStatus'), 'id' => 'frmPremiumStatusChange')) }}
						{!! Form::hidden('userId', 0, ['id' => 'premStatusId']) !!}
						{!! Form::hidden('premStatusActive', 0, ['id' => 'premStatusActive']) !!}
					{{ Form::close() }}

                </div>
            </div>
        </div>
    </div>
</div>
<div id="divModifyQuota"></div>
<div id="divModifyPremiumExpiration"></div>
@endsection