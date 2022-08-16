<!-- Modal -->
<div class="modal fade noprint" id="editEmployeeEmailModal" tabindex="-1" role="dialog" aria-labelledby="divModifyEmailModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyEmailTitle">Modify User Subscriber</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyEmail']) !!}
        <div class="modal-body">
          <div style="color: red;">
            Are you sure you want to modify subscriber for <b>{{ $empFullname }}</b>?
            <br/>
            The user account will be activated post this operation.
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12">  
                {!! Form::label('email', 'Email', ['class' => 'control-label']) !!}
                {{ Form::text('email', $empEmail, ['class' => 'form-control', 'id' => 'email', 'autocomplete' => 'off']) }}
            </div> 
          </div>     
        </div>
        <div class="modal-footer">
          <button type="submit" name="btnModifyEmail" id="btnModifyQuota" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save Changes</button>
        </div>
        {{ Form::hidden('empId', $employeeId) }}
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){
        $('#frmModifyEmail').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details          
                email: {
                    validators: {
                        notEmpty: {
                            message: 'Email is required'
                        },
                        emailAddress: {
	                        message: 'Invalid email address'
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
	        saveEmployeeEmailDetails($form);
	    });
    });

	function saveEmployeeEmailDetails(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('orgEmployee.changeEmail') !!}",
			dataType: "json",
			data: dataToBeSent,
		})
		.done(function(data){
			var status = data.status*1;
			var msg = data.msg;
			
			$('#editEmployeeEmailModal').modal('hide');	
			reloadEmployeeTable();	
			showSystemResponse(status, msg);
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
</script>