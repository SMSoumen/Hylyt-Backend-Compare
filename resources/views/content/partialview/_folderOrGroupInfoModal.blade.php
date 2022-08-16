<div id="folderOrGroupInfoModal" class="modal fade" role="dialog">
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
				<div class="row">
					<div class="col-md-12" align="center">
						{{ $dispName }}
						<br/>
						{{ $contentCount }} Item(s)
						<br/>
						({{ $contentSizeStr }})
					</div>
				</div>		
			</div>
		</div>
	</div>
</div>