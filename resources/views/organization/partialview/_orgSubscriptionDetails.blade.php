<?php
// $organizationId = 0;

// if(isset($organization))
// {
// 	$organizationId = $organization->organization_id;
// }

$actDate = NULL;
$expDate = NULL;
$userCount = NULL;
$quotaAllotted = NULL;
$sendReminderMail = 0;
$sendBirthdayMail = 0;
$retailShareEnabled = 0;
$sendContentAddedMail = 0;
$sendContentDeliveredMail = 0;

if(isset($orgSubscription))
{
	$actDate = $orgSubscription->actDtDisp;
	$expDate = $orgSubscription->expDtDisp;
	$userCount = $orgSubscription->user_count;
	$quotaAllotted = $orgSubscription->allotted_quota_in_gb;
	
	$sendReminderMail = $orgSubscription->reminder_mail_enabled;
	$sendBirthdayMail = $orgSubscription->birthday_mail_enabled;
	$retailShareEnabled = $orgSubscription->retail_share_enabled;
	$sendContentAddedMail = $orgSubscription->content_added_mail_enabled;
	$sendContentDeliveredMail = $orgSubscription->content_delivered_mail_enabled;
}

$assetBasePath = Config::get('app_config.assetBasePath'); 
?>
@if (isset($intJs))
	@for ($i = 0; $i < count($intJs); $i++)
	    <script type="text/javascript" src="{{ asset ($assetBasePath.$intJs[$i]) }}"></script>
	@endfor
@endif
{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmOrgSubscription']) !!}
  	<div class="row">
		<div class="col-md-12">
			<h4>
				Subscription Details
				@if($isView && ($modulePermissions->module_add == 1 || $modulePermissions->module_edit == 1))
					{!! Form::button('<i class="fa fa-pencil"></i>', ['type' => 'button', 'class' => 'btn btn-link', 'onclick' => "loadOrganizationSubscriptionDetailsView('$organizationId', 0);"]) !!}
				@endif
			</h4>
		</div>
	</div>
	<div class="row">
        <div class="col-md-4">
            <div class="form-group sandbox-container">
                {!! Form::label('activation_date', 'Date of Activation', ['class' => 'control-label']) !!}
				@if($isView)
                	<br/>
					{{ $actDate }}
				@else
                	{!! Form::text('activation_date', $actDate, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'activation_date']) !!}
				@endif         
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group sandbox-container">
                {!! Form::label('expiration_date', 'Date of Expiration', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
					{{ $expDate }}
				@else
                	{!! Form::text('expiration_date', $expDate, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'expiration_date']) !!}
                @endif        
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('user_count', 'User Count *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
					{{ $userCount }}
				@else
               		{!! Form::text('user_count', $userCount, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'user_count']) !!}
               	@endif
            </div>
        </div>
	</div>
	<div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('allotted_quota_in_gb', 'Allotted Quota (In GB) *', ['class' => 'control-label']) !!}
                @if($isView)
                	<br/>
        					{{ $quotaAllotted }}
        				@else
                	{!! Form::text('allotted_quota_in_gb', $quotaAllotted, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'allotted_quota_in_gb']) !!}
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('isRemMailEnabled', 1, NULL, ['class' => 'form-control', 'id' => 'reminder_mail_enabled']) }}
                    &nbsp;Enable Reminder Mails
          		</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('isBdayMailEnabled', 1, NULL, ['class' => 'form-control', 'id' => 'birthday_mail_enabled']) }}
                    &nbsp;Enable Birthday Mails
          		</label>
            </div>
        </div>
    </div>
	<div class="row">
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('isRetailShareEnabled', 1, NULL, ['class' => 'form-control', 'id' => 'retail_share_enabled']) }}
                    &nbsp;Enable Retail Share
          		</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('isContentAddedMailEnabled', 1, NULL, ['class' => 'form-control', 'id' => 'content_added_mail_enabled']) }}
                    &nbsp;Enable Content Added Mails
          		</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group" style="margin-top: 30px;">
            	<label>
                    {{ Form::checkbox('isContentDeliveredMailEnabled', 1, NULL, ['class' => 'form-control', 'id' => 'content_delivered_mail_enabled']) }}
                    &nbsp;Enable Content Delivered Mails
          		</label>
            </div>
        </div>
    </div>
       
    @if(!$isView)
	    <br/>
	    {!! Form::hidden('orgId', $organizationId, ['class' => 'orgId']) !!}
	    <div class="row">
			<div class="col-md-12" align="right">
			    {!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-orange', 'onclick' => "loadOrganizationSubscriptionDetailsView('$organizationId', 1);"]) !!}
			    &nbsp;
			    {!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Save', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
			</div>
		</div>
	@endif
{!! Form::close() !!}
<script>	
	var frmSubscriptionObj = $('#frmOrgSubscription');
	$(document).ready(function(){

  		$('#reminder_mail_enabled').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});
  		$('#birthday_mail_enabled').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});
  		$('#retail_share_enabled').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});
  		$('#content_added_mail_enabled').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});
  		$('#content_delivered_mail_enabled').iCheck({
    		checkboxClass: 'icheckbox_square-blue',
  		});
  		
  		@if(isset($sendReminderMail) && $sendReminderMail == 1)
        	$('#reminder_mail_enabled').iCheck('check');
        @endif
  		
  		@if(isset($sendBirthdayMail) && $sendBirthdayMail == 1)
        	$('#birthday_mail_enabled').iCheck('check');
        @endif
  		
  		@if(isset($retailShareEnabled) && $retailShareEnabled == 1)
        	$('#retail_share_enabled').iCheck('check');
        @endif
  		
  		@if(isset($sendContentAddedMail) && $sendContentAddedMail == 1)
        	$('#content_added_mail_enabled').iCheck('check');
        @endif
  		
  		@if(isset($sendContentDeliveredMail) && $sendContentDeliveredMail == 1)
        	$('#content_delivered_mail_enabled').iCheck('check');
        @endif

        @if($isView)
            $('#reminder_mail_enabled').prop('disabled', true);
            $('#birthday_mail_enabled').prop('disabled', true);
            $('#retail_share_enabled').prop('disabled', true);
            $('#content_added_mail_enabled').prop('disabled', true);
            $('#content_delivered_mail_enabled').prop('disabled', true);
        @endif

		<?php
		if(!$isView)
		{?>		
			frmSubscriptionObj.formValidation({
	            framework: 'bootstrap',
			    icon: {
			        valid: 'glyphicon glyphicon-ok',
			        invalid: 'glyphicon glyphicon-remove',
			        validating: 'glyphicon glyphicon-refresh'
			    },
	            fields: {           	
		             user_count: {
	                    validators: {
							notEmpty: {
								message: 'User Count is required'
							},
							numeric: {
	                            message: 'User Count is not a number'
	                        }
	                    }
		             },          	
		             allotted_quota_in_gb: {
	                    validators: {
							notEmpty: {
								message: 'Quota is required'
							},
							numeric: {
	                            message: 'Quota is not a number'
	                        }
	                    }
		             },
		        },
	        })
			.on('success.form.fv', function(e) {
	            // Prevent form submission
	            e.preventDefault();

	            // Some instances you can use are
	            var $form = $(e.target),        // The form instance
	                fv    = $(e.target).data('formValidation'); // FormValidation instance

	            // Do whatever you want here ...
	            saveOrganizationSubscriptionDetails($form);
	        });
	    <?php
	    }?>
	});
</script>