<!-- Modal -->
<div class="modal fade noprint" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">Change Password</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmChangePassword']) !!}
        <div class="modal-body">
          <div class="row">
	            <div class="col-md-12">
	            	<div class="form-group"> 
	            		{!! Form::label('oldPassword', 'Current Password', ['class' => 'control-label']) !!}
	               		{!! Form::password('oldPassword', NULL, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'oldPassword']) !!}  
	            	</div>
	            </div>
	            <div class="col-md-12">
	            	<div class="form-group"> 
	            		{!! Form::label('newPassword', 'New Password', ['class' => 'control-label']) !!}
	               		{!! Form::password('newPassword', NULL, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'newPassword']) !!}  
	            	</div>
	            </div>
	            <div class="col-md-12">
	            	<div class="form-group"> 
	            		{!! Form::label('newPassword2', 'Confirm Password', ['class' => 'control-label']) !!}
	               		{!! Form::password('newPassword2', NULL, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'newPassword2']) !!}  
	            	</div>
	            </div> 
                
            </div> 
          </div> 
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-success"><i class="fa fa-save fa-lg"></i>&nbsp;&nbsp;Save</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){
        $('#frmChangePassword').formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                //General Details          
                oldPassword: {
                    validators: {
                        notEmpty: {
                            message: 'Current Password is required'
                        },
						remote: {
							message: 'Incorrect Password',
							url: "{!!  url('/validateAppuserPassword') !!}",
							type: 'POST',
							data: function(validator, $field, value) 
							{			
								return {
									userId: getCurrentUserId(),
									loginToken: getCurrentLoginToken()
								};
							}
						}                  
                    }
                },     
                newPassword: {
                    validators: {
                        notEmpty: {
                            message: 'New Password is required'
                        },                   
                    }
                },     
                newPassword2: {
                    validators: {
                        notEmpty: {
                            message: 'Confirm Password is required'
                        },
						identical: {
							field: 'newPassword',
							message: 'New password and its confirm are not the same'
						}                   
                    }
                },
            }
        });
    })
    .on('success.form.fv', function(e) {
        // Prevent form submission
        e.preventDefault();
		var $form = $(e.target);
        $('#frmChangePassword').data('formValidation').resetForm();
        performChangePassword($form);
    });
</script>