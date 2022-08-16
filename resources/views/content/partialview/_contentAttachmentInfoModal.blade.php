<div id="contentAttachmentInfoModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Attachment Info		
				</h4>
			</div>
			<div class="modal-body">	
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('createDate', 'Creation Date', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('createDate', '', ['class' => 'control-label', 'id' => 'attCreateTs']) !!}
					</div>
				</div>	
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('attachmentSize', 'Attachment Size', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('attachmentSize', $attSizeStr, ['class' => 'control-label', 'id' => 'attSizeStr']) !!}
					</div>
				</div>	
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('attachmentSource', 'Source', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('attachmentSource', $attSourceStr, ['class' => 'control-label', 'id' => 'attSource']) !!}
					</div>
				</div>	
			</div>
		</div>
	</div>
</div>
<script>
	var createTs = getDispDateTimeFromTimestamp({{ $createTs }});
	$('#attCreateTs').text(createTs);
</script>