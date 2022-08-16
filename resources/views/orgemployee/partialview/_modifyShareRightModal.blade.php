@php
$empExists = false;
$empFullname = "";
$isSracShareEnabled = NULL;
$isSracOrgShareEnabled = NULL;
$isSracRetailShareEnabled = NULL;
$isCopyToProfileEnabled = NULL;
$isSocShareEnabled = NULL;
$isSocFacebookShareEnabled = NULL;
$isSocTwitterShareEnabled = NULL;
$isSocLinkedinShareEnabled = NULL;
$isSocWhatsappShareEnabled = NULL;
$isSocEmailShareEnabled = NULL;
$isSocSmsShareEnabled = NULL;
$isSocOtherShareEnabled = NULL;
$orgEmpId = 0;
if(isset($employee))
{
	$empExists = true;
	$empFullname = $employee->employee_name;
	$orgEmpId = $employee->employee_id;
	
	if($employee->is_srac_share_enabled == 1)
		$isSracShareEnabled = TRUE;
	if($employee->is_srac_org_share_enabled == 1)
		$isSracOrgShareEnabled = TRUE;
	if($employee->is_srac_retail_share_enabled == 1)
		$isSracRetailShareEnabled = TRUE;
	if($employee->is_copy_to_profile_enabled == 1)
		$isCopyToProfileEnabled = TRUE;
		
	if($employee->is_soc_share_enabled == 1)
		$isSocShareEnabled = TRUE;
	if($employee->is_soc_facebook_enabled == 1)
		$isSocFacebookShareEnabled = TRUE;
	if($employee->is_soc_twitter_enabled == 1)
		$isSocTwitterShareEnabled = TRUE;
	if($employee->is_soc_linkedin_enabled == 1)
		$isSocLinkedinShareEnabled = TRUE;
	if($employee->is_soc_whatsapp_enabled == 1)
		$isSocWhatsappShareEnabled = TRUE;
	if($employee->is_soc_email_enabled == 1)
		$isSocEmailShareEnabled = TRUE;
	if($employee->is_soc_sms_enabled == 1)
		$isSocSmsShareEnabled = TRUE;
	if($employee->is_soc_other_enabled == 1)
		$isSocOtherShareEnabled = TRUE;
}
elseif($isMulti) {
	$empExists = true;
}
@endphp
<!-- Modal -->
<div class="modal fade noprint" id="divModifyShareRightModal" tabindex="-1" role="dialog" aria-labelledby="divModifyQuotaModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">Modify User Share Rights</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyShareRight']) !!}
        @if($empExists)
	        <div class="modal-body" id="divVoidInvoice">
	        
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<div class="row">
								<div class="col-md-6">
									<label>
										{!! Form::checkbox('is_srac_share_enabled', 1, $isSracShareEnabled, ['id' => 'is_srac_share_enabled']) !!}
										&nbsp;&nbsp;SRAC Share
									</label>
								</div> 
							</div>
						</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-md-4">
								<label>
									{!! Form::checkbox('is_srac_org_share_enabled', 1, $isSracOrgShareEnabled, ['class' => 'sracShare']) !!}
									&nbsp;&nbsp;Organization Users
								</label>
							</div> 
							@if($hasRetailShareRights)
								<div class="col-md-4">
									<label>
										{!! Form::checkbox('is_srac_retail_share_enabled', 1, $isSracRetailShareEnabled, ['class' => 'sracShare']) !!}
										&nbsp;&nbsp;Retail Users
									</label>
								</div>
							@endif
						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<div class="row">
								<div class="col-md-6">
									<label>
										{!! Form::checkbox('is_copy_to_profile_enabled', 1, $isCopyToProfileEnabled, ['id' => 'is_copy_to_profile_enabled']) !!}
										&nbsp;&nbsp;Copy To Profile
									</label>
								</div> 
							</div>
						</h3>
					</div>
				</div>
				
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<div class="row">
								<div class="col-md-6">
									<label>
										{!! Form::checkbox('is_soc_share_enabled', 1, $isSocShareEnabled, ['id' => 'is_soc_share_enabled']) !!}
										&nbsp;&nbsp;Social Share
									</label>
								</div> 
							</div>
						</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-md-4">
								<label>
									{!! Form::checkbox('is_soc_facebook_enabled', 1, $isSocFacebookShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;Facebook
								</label>
							</div> 
							<div class="col-md-4">
				                <label>
									{!! Form::checkbox('is_soc_twitter_enabled', 1, $isSocTwitterShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;Twitter
								</label>
							</div>
							<div class="col-md-4">
								<label>
									{!! Form::checkbox('is_soc_linkedin_enabled', 1, $isSocLinkedinShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;LinkedIn
								</label>
							</div> 
						</div>
						<br/>
						<div class="row">
							<div class="col-md-4">
				                <label>
									{!! Form::checkbox('is_soc_whatsapp_enabled', 1, $isSocWhatsappShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;WhatsApp
								</label>
							</div> 
							<div class="col-md-4">
								<label>
									{!! Form::checkbox('is_soc_email_enabled', 1, $isSocEmailShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;Email
								</label>
							</div> 
							<div class="col-md-4">
				                <label>
									{!! Form::checkbox('is_soc_sms_enabled', 1, $isSocSmsShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;SMS
								</label>
							</div> 
						</div>
						<br/>
						<div class="row">
							<div class="col-md-4">
								<label>
									{!! Form::checkbox('is_soc_other_enabled', 1, $isSocOtherShareEnabled, ['class' => 'socShare']) !!}
									&nbsp;&nbsp;Other
								</label>
							</div>  
						</div>
					</div>
				</div>         
	        </div>
	        <div class="modal-footer">
	          <button type="submit" name="btnModifyQuota" id="btnModifyQuota" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save Changes</button>
	        </div>
	    @else
	    	<div class="modal-body"> 
	    		<div class="row" style="color: red;">
		          	<div class="col-md-12">
		          		<b>User hasn't registered yet.</b>
		          	</div>	          	
		          </div>     
        	</div>
	    @endif
	    
        {{ Form::hidden('isMulti', $isMulti) }}
        @if($isMulti == 0)
        	{{ Form::hidden('empId', $orgEmpId) }}
        @else
        	{{ Form::hidden('empIdArr', $orgEmpIdArr) }}
        @endif
        
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	var skipShareCheckedAction = false;
    $(document).ready(function(){
		
		$('#is_srac_share_enabled').iCheck({
			checkboxClass: 'icheckbox_square-yellow',
		}).on('ifChecked', function(event){
			if(!skipShareCheckedAction)
				$("input.sracShare").iCheck('check');
			else
				skipShareCheckedAction = false;
		}).on('ifUnchecked', function(event){
			$("input.sracShare").iCheck('uncheck');
		});
		
		$('input.sracShare').iCheck({
			checkboxClass: 'icheckbox_square-yellow',
		}).on('ifChecked', function(event){
			skipShareCheckedAction = true;
			$("#is_srac_share_enabled").iCheck('check');
		});
		
		$('#is_soc_share_enabled').iCheck({
			checkboxClass: 'icheckbox_square-blue',
		}).on('ifChecked', function(event){
			if(!skipShareCheckedAction)
				$("input.socShare").iCheck('check');
		}).on('ifUnchecked', function(event){
			$("input.socShare").iCheck('uncheck');
		});
		
		$('#is_copy_to_profile_enabled').iCheck({
			checkboxClass: 'icheckbox_square-green',
		});
		
		$('input.socShare').iCheck({
			checkboxClass: 'icheckbox_square-blue',
		}).on('ifUnchecked', function(event){
			var socShareCheckedCnt = getSocShareCheckedCount();
			if(socShareCheckedCnt == 0)
			{
				$("#is_soc_share_enabled").iCheck('uncheck');
			}
		}).on('ifChecked', function(event){
			var socShareCheckedCnt = getSocShareCheckedCount();
			if(socShareCheckedCnt > 0)
			{
				skipShareCheckedAction = true;
				$("#is_soc_share_enabled").iCheck('check');
				skipShareCheckedAction = false;
			}
		});
		
        $('#frmModifyShareRight').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details
            }
        }).on('success.form.fv', function(e) {
	        // Prevent form submission
	        e.preventDefault();

	        // Some instances you can use are
	        var $form = $(e.target),        // The form instance
	            fv    = $(e.target).data('formValidation'); // FormValidation instance

	        // Do whatever you want here ...
	        saveEmployeeShareRightDetails($form);
	    });
	});
    
    function getSocShareCheckedCount()
    {
    	var checkedCnt = 0;
		$( ".socShare" ).each(function( index ) {
			var chckValue = $(this).iCheck('update')[0].checked;
			if(chckValue)
				checkedCnt++;
		});
		return checkedCnt;
	}

	function saveEmployeeShareRightDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('orgEmployee.saveShareRightDetails') !!}",
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#divModifyShareRightModal').modal('hide');	
			reloadEmployeeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>