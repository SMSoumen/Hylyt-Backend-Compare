<style>
	.infoRow
	{
		margin-top: 12px !important;
		margin-left: 12px !important;
	}
	.infoBody
	{
		font-size: 16px !important;
		margin-bottom: 20px !important;
	}
</style>
<div id="orgUserInformationModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					Profile Details
				</h4>
			</div>
			<div class="modal-body infoBody">
				<div class="row">
					<div class="col-md-12" align="center">
						@if(isset($orgLogoUrl) && $orgLogoUrl != "")
							<img src="{{ $orgLogoUrl }}" height="50px"/>
						@endif
						<br/>
						{{ $orgName }}
						<hr/>
					</div>
				</div>
				<div class="row infoRow">
					<div class="col-md-6">
						Email:
						<br/>
						<b>{{ $orgEmail }}</b>
					</div>
					<div class="col-md-6">
						Phone:
						<br/>
						<b>{{ $orgPhone }}</b>
					</div>
				</div>
				<div class="row infoRow">
					<div class="col-md-6">
						Website:
						<br/>
						<b>{{ $orgWebsite }}</b>
					</div>
				</div>
				<div class="row infoRow">
					<div class="col-md-12">
						Description:
						<br/>
						<b>{!! $orgDescription !!}</b>
					</div>
				</div>
				@if(isset($usrFieldsArr) && count($usrFieldsArr) > 0)
					<div class="row">
						<div class="col-md-12" align="center">
							<hr/>
						</div>
					</div>
					@for($i=0; $i < count($usrFieldsArr); $i++)
						@php
						$usrField = $usrFieldsArr[$i];
						@endphp
						<div class="row infoRow">
							<div class="col-md-12">
								{{ $usrField['fldTitle'] }}:
								<br/>
								<b>{{ $usrField['fldValue'] }}</b>
							</div>
						</div>
					@endfor
				@endif
			</div>
		</div>
	</div>
</div>