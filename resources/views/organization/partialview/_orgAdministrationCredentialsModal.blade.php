@php
@endphp
<!-- Modal -->
<div class="modal fade noprint" id="divOrgAdminCredentialsModal" tabindex="-1" role="dialog" aria-labelledby="divModifyQuotaModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="modifyQuotaTitle">User Credentials</h4>
      </div>
    	<div class="modal-body">
				<div class="row">
					<div class="col-md-4">  
						<b>Name</b>
					</div> 
					<div class="col-md-5">  
						<b>Email</b>
					</div> 
					<div class="col-md-3">  
						<b>Password</b>
					</div> 
				</div>
				<div class="row">
					<div class="col-md-4">  
						{{ $name }}
					</div> 
					<div class="col-md-5">  
						{{ $email }}
					</div> 
					<div class="col-md-3">  
						{{ $password }}
					</div> 
				</div>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){

    });
</script>