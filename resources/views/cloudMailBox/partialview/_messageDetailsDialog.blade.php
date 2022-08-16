<?php
$tagId = 0;
$tagName = "";
if(isset($messageDetails))
{
	$messageId = $messageDetails['messageId'];
	$messageSnippet = $messageDetails['snippet'];
	$mailDateTs = $messageDetails['mailDateTs'];
	$cloudMailBoxTypeCode = $messageDetails['cloudMailBoxTypeCode'];
	$mailSubject = $messageDetails['mailSubject'];
	$senderOrReceiverEmail = $messageDetails['senderOrReceiverEmail'];
	$mailContainerLabel = $messageDetails['mailContainerLabel'];
	$isUnread = $messageDetails['isUnread'];
	$isReceived = $messageDetails['isReceived'];
	$isSent = $messageDetails['isSent'];

	$detailedContentHtml = $messageDetails['detailedContentHtml'];
	$detailedContentPlain = $messageDetails['detailedContentPlain'];
	$attachmentArr = $messageDetails['attachments'];

	$mailDateStr = dbToDispDateTimeWithTZ($mailDateTs, $tzStr);
}
?>
<script>
	$(document).ready(function(){

	});
</script>

<div id="cloudMailBoxMessageDetailModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Mail Details
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<b>{{ $mailSubject }}</b>
							<br/>
							{{ $mailDateStr }}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						[{{ $mailContainerLabel }}] - {{ $senderOrReceiverEmail }}
					</div>
				</div>
				<br/>

				<div class="row">
					<div class="col-md-12">
						{!! $detailedContentHtml !!}
					</div>
				</div>

				@if(isset($attachmentArr) && count($attachmentArr) > 0)

					<br/>

					<div class="row">
						<div class="col-md-12">
							<b>Attachment(s)</b>
						</div>
					</div>

					@foreach($attachmentArr as $attObj)

						@php						
						$attachmentId = $attObj['attachmentId'];
						$fileName = $attObj['fileName'];
						$fileSize = $attObj['fileSize'];
						$fileMimeType = $attObj['fileMimeType'];
						@endphp

						<div class="row" style="padding-bottom: 5px;">
							<div class="col-md-10">
								{{ $fileName }}
								<br/>
								{{ formatBytesStr($fileSize) }}
							</div>
							<div class="col-md-2">
								<button type="button" class="btn btn-default btn-sm" onclick="loadCloudMailBoxMessageAttachmentDetailsDialog('{{ $orgKey }}', '{{ $cloudMailBoxTypeCode }}', '{{ $messageId }}', '{{ $attachmentId }}', '{{ $fileName }}');" data-toggle="tooltip" title="View Message">
						    		<i class="fa fa-eye"></i>
						    	</button>
							</div>
						</div>

					@endforeach

				@endif

				<div id="divCloudMailBoxMessageAttachmentOperations"></div>
								
			</div>
		</div>
	</div>
</div>