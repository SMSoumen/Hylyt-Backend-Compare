<div id="videoTutorialModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					HyLyt Web Video Tutorial		
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<iframe width="560" height="315" src="{{ $videoTutorialEmbedUrl }}" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$("#videoTutorialModal").on('hidden.bs.modal', function (e) {
	    $("#videoTutorialModal iframe").attr("src", $("#videoTutorialModal iframe").attr("src"));
	});
</script>