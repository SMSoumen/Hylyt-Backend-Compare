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

	$mailDateStr = dbToDispDateTimeWithTZ($mailDateTs, $tzStr);
}
?>
<script>
	$(document).ready(function(){

	});
</script>

<div id="cloudMailBoxMessageAddModal" class="modal fade" role="dialog">
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
						{{ $messageSnippet }}
					</div>
				</div>
								
			</div>
		</div>
	</div>
</div>