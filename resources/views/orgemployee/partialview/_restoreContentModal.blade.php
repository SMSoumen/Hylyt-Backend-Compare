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
	
	if($minQuotaVal <= 0)
		$minQuotaVal = $attachmentAllottedKb;
	
	$minQuotaVal = ceil($minQuotaVal/1024);
	
	if($minQuotaVal <= 0)
		$minQuotaVal = 1;
}
@endphp
<!-- Modal -->
<div class="modal fade noprint" id="divRestoreContentModal" tabindex="-1" role="dialog" aria-labelledby="divRestoreContentModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="restoreContentTitle">Restore User Content</h4>
      </div>
      {!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmRestoreContent']) !!}
        <div class="modal-body">
    		@if($empExists)
				@if($restoreContentCount > 0)
					<div class="row">
						<div class="col-md-6">  
							<div class="col-md-12"><b>Restore Content Count</b></div>
							<div class="col-md-12">{{ $restoreContentCount }}</div>
						</div> 
						<div class="col-md-6">  
							<div class="col-md-12"><b>Restore Content Size</b></div>
							<div class="col-md-12">{{ $restoreContentSizeStr != '' ? $restoreContentSizeStr : '-' }}</div>
						</div> 
					</div>  
					<br/>
					<div class="row">
						<div class="col-md-6">  
							<div class="col-md-12"><b>Oldest Content Date</b></div>
							<div class="col-md-12" id="divOldestContentDt"></div>
						</div> 
						<div class="col-md-6">  
							<div class="col-md-12"><b>Latest Content Date</b></div>
							<div class="col-md-12" id="divNewestContentDt"></div>
						</div> 
					</div> 
					<br/>
				@else
					<div class="row">
						<div class="col-md-12">  
							No content(s) available for restore
						</div> 
					</div> 
					<br/>
				@endif
	        @else
	          <div class="row" style="color: red;">
	          	<div class="col-md-12">
	          		<b>User hasn't registered yet.</b>
	          	</div>	          	
	          </div>
	        @endif       
        </div>
        @if($empExists && $restoreContentCount > 0)
	        <div class="modal-footer">
	          <button type="submit" name="btnRestoreContent" id="btnRestoreContent" class="btn btn-primary btn-success">Restore Content</button>
	        </div>
	    @endif
        {{ Form::hidden('empId', $orgEmpId) }}
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>

	function getDispDateFromTimestamp(utcTs)
	{
		var locDateStr = "";
		if(utcTs != null && utcTs != "" && utcTs*1 > 0)
		{
			var day = moment(utcTs);		
			locDateStr = day.format('DD-MM-YYYY');
			
			//var locDate = new Date(utcTs);
			//locDateStr = paddedNumberFormat(locDate.getDate(), 2) + "-" + paddedNumberFormat(locDate.getMonth()+1, 2) + "-" + locDate.getFullYear() + " " + paddedNumberFormat(locDate.getHours(), 2) + ":" + paddedNumberFormat(locDate.getMinutes(), 2);
		}
		return locDateStr;
	}

    $(document).ready(function(){
		var oldestContentDateTs = getDispDateFromTimestamp({{ $oldestContentDate }});
		if(oldestContentDateTs == '')
		{
			oldestContentDateTs = '-';
		}
		$('#divOldestContentDt').html(oldestContentDateTs);

		var newestContentDateTs = getDispDateFromTimestamp({{ $newestContentDate }});
		if(newestContentDateTs == '')
		{
			newestContentDateTs = '-';
		}
		$('#divNewestContentDt').html(newestContentDateTs);

        $('#frmRestoreContent').formValidation({
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
	        saveEmployeeRestoreContent($form);
	    });
    });

	function saveEmployeeRestoreContent(frmObj)
	{
		var dataToBeSent = $(frmObj).serialize()+"&usrtoken="+"{{ $usrtoken }}";

		bootbox.confirm({
		    message: "Do you wish to restore the content(s)?",
		    buttons: {
		        confirm: {
		            label: 'Yes',
		            className: 'btn-success'
		        },
		        cancel: {
		            label: 'No',
		            className: 'btn-danger'
		        }
		    },
		    callback: function (result) {

		    	if(result == true)
		    	{
		    		$.ajax({
						type: 'POST',
						crossDomain: true,
						url: "{!!  route('orgEmployee.performContentRestore') !!}",
						dataType: "json",
						data: dataToBeSent,
					})
					.done(function(data){
						var status = data.status*1;
						var msg = data.msg;
						
						$('#divRestoreContentModal').modal('hide');	
						reloadEmployeeTable();	
						showSystemResponse(status, msg);
					})
					.fail(function(xhr,ajaxOptions, thrownError) {
					})
					.always(function() {
					});
		    	}			    	
		    }
		});


					
	}
</script>