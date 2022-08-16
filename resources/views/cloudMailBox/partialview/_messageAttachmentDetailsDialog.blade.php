<?php
if(isset($messageAttachmentDetails))
{
	$messageId = $messageAttachmentDetails['messageId'];
	$attachmentId = $messageAttachmentDetails['attachmentId'];
	$fileName = $messageAttachmentDetails['fileName'];
	$fileSize = $messageAttachmentDetails['fileSize'];
	$fileContent = $messageAttachmentDetails['fileContent'];

	$fileSizeStr = formatBytesStr($fileSize);
}
?>
<script>
	$(document).ready(function(){

	});
</script>

<div id="cloudMailBoxMessageAttachmentDetailModal" class="modal fade" role="dialog">
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
							<b>{{ $fileName }}</b>
							<br/>
							{{ $fileSizeStr }}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">

					</div>
				</div>
								
			</div>
		</div>
	</div>
</div>