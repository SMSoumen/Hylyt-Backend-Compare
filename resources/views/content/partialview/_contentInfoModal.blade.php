<div id="contentInfoModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					{{ $modalTitle or null }}		
				</h4>
			</div>
			<div class="modal-body">
				@if($isConversation)
					<div class="row">
						<div class="col-md-6">
							{!! Form::label('threadCount', '# of Threads', ['class' => 'control-label']) !!}
						</div>
						<div class="col-md-6" align="right">
							{!! Form::label('threadCount', $conversationThreadCount, ['class' => 'control-label']) !!}
						</div>
					</div>	
				@endif
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('noteSize', 'Note Size', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('noteSize', $noteSizeStr, ['class' => 'control-label']) !!}
					</div>
				</div>		
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('createDate', 'Creation Date', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('createDate', '', ['class' => 'control-label', 'id' => 'createTs']) !!}
					</div>
				</div>	
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('updateDate', 'Last Modified Date', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('updateDate', '', ['class' => 'control-label', 'id' => 'updateTs']) !!}
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						{!! Form::label('attachmentCount', 'Total Attachment Count', ['class' => 'control-label']) !!}
					</div>
					<div class="col-md-6" align="right">
						{!! Form::label('attachmentCount', '', ['class' => 'control-label', 'id' => 'attCnt']) !!}
					</div>
				</div>		
			</div>
		</div>
	</div>
</div>
<script>
	var createTs = getDispDateTimeFromTimestamp({{ $createTs }});
	var updateTs = getDispDateTimeFromTimestamp({{ $updateTs }});
	$('#createTs').text(createTs);
	$('#updateTs').text(updateTs);
	$('#attCnt').text({{ $attachmentCnt }});
</script>