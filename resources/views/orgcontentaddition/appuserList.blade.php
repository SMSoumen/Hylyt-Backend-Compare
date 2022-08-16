@extends('ws_template')

@section('int_scripts')
<script>
function addContentForSelUsers()
{
	var selIdArr = [];
	$( "input.empIsSelected" ).each(function( index ) {
		const isChecked = $(this).iCheck('update')[0].checked;
		const empId = $(this).val();
		console.log( index + " : " + isChecked + ' : ' + empId);
		if(isChecked === true) {
			selIdArr.push(empId);
		}
	});

	if(selIdArr.length > 0)
	{
		selIdArr = JSON.stringify(selIdArr);

		bootbox.dialog({
			message: "Do you really want to add this Content for All Selected Users?",
			title: "Confirm Add Content",
				buttons: {
					yes: {
					label: "Yes",
					className: "btn-primary",
					callback: function() {
						var contId = $('#send_id').val();
						var formData = getAppuserDataForTableForForm();
						formData += '&contId='+contId;
						formData += '&empIdArr='+selIdArr;
						
						$.ajax({
							type: "POST",
							crossDomain: true,
							url: "{!!  route('orgContentAddition.addSelAppuserContent') !!}",
							dataType: "json",
							data: formData,
						})
						.done(function(data) {
							if(data.msg != "")
							{
								ShowBootboxNotification("Alert", data.msg);
								return;
							}
							window.location = 'contentAddition';
							
						})
						.fail(function(xhr, ajaxOptions, thrownError) {
						})
						.always(function() {
						});
					}
				},
				no: {
					label: "No",
					className: "btn-primary",
					callback: function() {
					}
				}
			}
		});
	}
	else
	{
		showSystemResponse(-1, 'Select atleast 1 appuser');
	}
}
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">		
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		    	<div class="col-md-6">
		    		<h3 class="box-title">Filter Appusers</h3>
		    	</div>
		        <div class="col-md-6" align="right">
		        	<button type="button" name="btnSend" id="btnSend" class="btn btn-primary btn-success btn-sm" onclick="addContentForSelUsers();"><i class="fa fa-send"></i>&nbsp;&nbsp;<b>Send Content</b></button>
		        </div>
		    </div>
            <div class="box-body">
			    @include('orgemployee.partialview._advancedSearch')
			    @include('orgemployee.partialview._employeeList')
				{{ Form::hidden('cont_add_id', $addContentId, ['id' => 'send_id']) }}
			</div>
		</div>
	</div>
</div>
@endsection