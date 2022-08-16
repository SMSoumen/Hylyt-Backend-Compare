@php
@endphp
<!-- Modal -->
<div class="modal fade noprint" id="divViewEmployeeCredentialsModal" tabindex="-1" role="dialog" aria-labelledby="divModifyQuotaModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">User Credentials</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmModifyQuota']) !!}
        <div class="modal-body">
        	@if(isset($compiledCredentialData) && is_array($compiledCredentialData))
				<div class="row">
					<div class="col-md-4">  
						<b>Name</b>
					</div> 
					<div class="col-md-5">  
						<b>Email</b>
					</div> 
					<div class="col-md-3">  
						<b>Code</b>
					</div> 
				</div>
        		@foreach($compiledCredentialData as $compiledCredentialObj)
					<div class="row">
						<div class="col-md-4">  
							{{ $compiledCredentialObj['empName'] }}
						</div> 
						<div class="col-md-5">  
							{{ $compiledCredentialObj['empEmail'] }}
						</div> 
						<div class="col-md-3">  
							{{ $compiledCredentialObj['verificationCode'] }}
						</div> 
					</div>
				@endforeach
			@endif
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){

    });
</script>