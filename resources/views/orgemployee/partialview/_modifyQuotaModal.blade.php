@php
$empExists = false;
$empFullname = "";
$attachmentAllottedKb = 0;
$orgEmpId = 0;
$minQuotaVal = 1;
if(isset($employee))
{
	$empExists = true;
	$empFullname = $employee->employee_name;
	$orgEmpId = $employee->employee_id;
	$attachmentAllottedKb = $employee->attachment_kb_allotted;
	$attachmentAvailKb = $employee->attachment_kb_available;
	$minQuotaVal = $attachmentAllottedKb - $attachmentAvailKb;
	
	/* if($minQuotaVal <= 0)
		$minQuotaVal = $attachmentAllottedKb; */
	
	$minQuotaVal = ceil($minQuotaVal/1024);
	
	if($minQuotaVal <= 0)
		$minQuotaVal = 1;
}
elseif($isMulti) {
	$empExists = true;
}
@endphp
<!-- Modal -->
<div class="modal fade noprint" id="divModifyQuotaModal" tabindex="-1" role="dialog" aria-labelledby="divModifyQuotaModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">Modify User Quota</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyQuota']) !!}
        <div class="modal-body">
        	@if($empExists)
	          <div style="color: red;">
	            Are you sure you want to modify space quota for <b>{{ $empFullname }}</b>?
	          </div>
	          <br/>
	          <div class="row">
	            <div class="col-md-12 form-group {{ $errors->has('empQuota') ? 'has-error' : ''}}">  
	                {!! Form::label('empQuota', 'Allotted Space Quota', ['class' => 'control-label']) !!}
	                <div class="input-group">
	                    <span class="input-group-addon"><i class="fa fa-database"></i></span>
	                    {{ Form::text('empQuota', ceil($attachmentAllottedKb/1024), ['class' => 'form-control', 'id' => 'empQuota', 'autocomplete' => 'off']) }}
	                    <span class="input-group-addon">MB</span>
	                </div>
	                {!! $errors->first('empQuota', '<p class="help-block">:message</p>') !!}
	            </div> 
	          </div>  
	        @else
	          <div class="row" style="color: red;">
	          	<div class="col-md-12">
	          		<b>User hasn't registered yet.</b>
	          	</div>	          	
	          </div>
	        @endif       
        </div>
        @if($empExists)
	        <div class="modal-footer">
	          <button type="submit" name="btnModifyQuota" id="btnModifyQuota" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save Changes</button>
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
    $(document).ready(function(){
        $('#frmModifyQuota').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details          
                empQuota: {
                    validators: {
                        notEmpty: {
                            message: 'Allotted Space is required'
                        },
                        numeric: {
                            message: 'The value is not a number',
                            thousandsSeparator: '',
                            decimalSeparator: ''
                        } ,
                        greaterThan: {
                            value: {{ $minQuotaVal }},
                        }                     
                    }
                }
            }
        }).on('success.form.fv', function(e) {
	        // Prevent form submission
	        e.preventDefault();

	        // Some instances you can use are
	        var $form = $(e.target),        // The form instance
	            fv    = $(e.target).data('formValidation'); // FormValidation instance

	        // Do whatever you want here ...
	        saveEmployeeQuotaDetails($form);
	    });
    });

	function saveEmployeeQuotaDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('orgEmployee.saveQuotaDetails') !!}",
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#divModifyQuotaModal').modal('hide');	
			reloadEmployeeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>