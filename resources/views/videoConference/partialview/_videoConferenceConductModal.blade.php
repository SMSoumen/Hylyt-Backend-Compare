<div id="conductVideoConferenceModal" class="modal fade" data-backdrop="static" role="dialog" data-keyboard="false">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header content-detail-modal-header">
				<button type="button" class="close modal-content-close" data-dismiss="modal" onclick="exitAndCloseModal()">
					&times;
				</button>
				<h4 class="modal-title">
					{{ $page_description or null }}	
				</h4>
			</div>
			<div class="modal-body content-detail-modal-body">

				@include('videoConference.partialview._videoConferenceConductSubView')

			</div>
			<!-- <div class="modal-footer content-detail-modal-footer">
				<div class="col-md-8">
					
				</div>
				<div class="col-md-4">

				</div>
			</div> -->
		</div>
	</div>
</div>