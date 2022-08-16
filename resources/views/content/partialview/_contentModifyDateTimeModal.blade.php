@php
	$contentTypeIdR = Config::get('app_config.content_type_r');
	$contentTypeIdA = Config::get('app_config.content_type_a');
	$contentTypeIdC = Config::get('app_config.content_type_c');
@endphp
<style>
	.divOptionList
	{
		max-height: 400px;
    	overflow: auto;
	}
</style>
<div id="contentModifyDateTimeModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmContentDependency', 'onsubmit' => 'return false;']) !!}
				<div class="modal-header">
					<!--<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>-->
					<h4 class="modal-title">
						{{ $modalTitle or null }}		
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-1">
						</div>		
						@if($contentTypeId == $contentTypeIdR || $contentTypeId == $contentTypeIdC)			
							<div class="col-md-5">
								<div class="form-group detailsRow" id="divFromDateTime">
									<div class="col-md-offset-1 col-md-11">
										<div class='input-group date' id='fromDtTmPartOp'>
						                    <input type='text' class="form-control" />
						                    <span class="input-group-addon">
						                        <span class="glyphicon glyphicon-calendar"></span>
						                    </span>
			           	 				</div>
										{!! Form::hidden('fromTimeStampPartOp', $fromTs, ['id' => 'fromTimeStampPartOp']) !!}
									</div>
								</div>
							</div>
						@endif
						@if($contentTypeId == $contentTypeIdC)		
							<div class="col-md-5">
								<div class="form-group detailsRow" id="divToDateTime">
									<div class="col-md-offset-1 col-md-11">
										<div class='input-group date' id='toDtTmPartOp'>
						                    <input type='text' class="form-control" />
						                    <span class="input-group-addon">
						                        <span class="glyphicon glyphicon-calendar"></span>
						                    </span>
			           	 				</div>
										{!! Form::hidden('toTimeStampPartOp', $toTs, ['id' => 'toTimeStampPartOp']) !!}
									</div>
								</div>
							</div>
						@endif
					</div>	
					<div class="col-md-1">
					</div>	
				</div>
				<div class="modal-footer">
					<div class="col-md-12" align="right">
						@if($hasDone == 1)
							{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Done', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'doneClicked();', 'id' => 'btnDone']) !!}
						@endif	
						@if($hasCancel == 1)
							{!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-danger', 'onclick' => 'cancelClicked();', 'id' => 'btnCancel']) !!}
						@endif					
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
<style>
</style>
<script>
	
	$(document).ready(function(){

			
			$('#fromDtTmPartOp').datetimepicker({
				date: new Date({{ $fromTs }}),
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#fromTimeStampPartOp").val(dtTimestamp);

				@if($contentTypeId == $contentTypeIdC)
					const calcToDtTm = moment(dtTimestamp).add(1, 'hour');
					const calcToDtTmTs = calcToDtTm.unix() * 1000;

					$('#toTimeStampPartOp').val(calcToDtTmTs);
					$('#toDtTmPartOp').data("DateTimePicker").date(calcToDtTm.format('DD/MM/YYYY HH:mm'));
				@endif
			});
			
			$('#fromTimeStampPartOp').val(new Date({{ $fromTs }}).getTime());

			var consToDt;
			@if(isset($toTs))
				consToDt = new Date({{ $toTs }})
			@else
				consToDt = new Date().addHours(1);
			@endif
			
			$('#toDtTmPartOp').datetimepicker({				
				date: consToDt,
				format:'DD/MM/YYYY HH:mm'
			}).on('dp.change', function (e) {
				dtTimestamp = e.date.unix();
				dtTimestamp *= 1000;
				$("#toTimeStampPartOp").val(dtTimestamp);
			});
			
			$('#toTimeStampPartOp').val(consToDt.getTime());
  		
	});
	
	function doneClicked() 
	{
		var fromTs = $('#fromTimeStampPartOp').val();
		var toTs = $('#toTimeStampPartOp').val();
		var tempConOrgId = $('#conOrgId').val();

		var dataToSend = "orgId=" + tempConOrgId + "&userId=" + getCurrentUserId() + "&loginToken=" + getCurrentLoginToken();
		dataToSend += "&contentTypeId=" + {{ $contentTypeId }} + "&fromTs=" + fromTs + "&toTs=" + toTs;
		dataToSend += "&id=" + '{{ $contentId }}' + "&isFolder=" + '{{ $isFolderFlag }}' + '&isConversation=' + '{{ $isConversationFlag }}';

		var urlStr = "{!! route('content.performContentDateTimeModification') !!}";
		$.ajax({
			type: "POST",
			url: urlStr,
			crossDomain : true,
			dataType: 'json',
			data: dataToSend,
			success: function(data)
			{
				$("#contentModifyDateTimeModal").modal("hide");

				if(data.status*1 > 0)
				{	
					if(data.msg != "")
						successToast.push(data.msg);

					performContentModificationPush('{{ $contentId }}', '{{ $isFolderFlag }}', tempConOrgId);

					reloadContentDetailsModal();	
				}
				else
				{
					if(data.msg != "")
						errorToast.push(data.msg);
				}
			}
		});
	}
	
	function cancelClicked() 
	{
		$("#contentModifyDateTimeModal").modal("hide");
	}

</script>