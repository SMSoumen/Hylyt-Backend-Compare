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
<div id="userInformationModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">
					&times;
				</button>
				<h4 class="modal-title">
					User Information
				</h4>
			</div>
			<div class="modal-body infoBody">
				@if(isset($photoThumbUrl) && $photoThumbUrl != "")
					<div class="row infoRow">
						<div class="col-md-12" align="left">
							<img src="{{ $photoThumbUrl }}" style="border-radius: 50%;width: 100px;height: 100px;"/>
						</div>
					</div>
				@endif
				<div class="row infoRow">
					<div class="col-md-12">
						Name:
						<br/>
						<b>{{ $user->fullname }}</b>
					</div>
				</div>	
				<div class="row infoRow">
					<div class="col-md-12">
						Email:
						<br/>
						<b>{{ $user->email }}</b>
					</div>
				</div>	
				<div class="row infoRow">
					<div class="col-md-12">
						Phone Number:
						<br/>
						<b>{{ $user->contact }}</b>
					</div>
				</div>
				<div class="row infoRow">
					<div class="col-md-12">
						Gender:
						<br/>
						 <b>{{ $user->gender }}</b>
					</div>
				</div>
				<div class="row infoRow">
					<div class="col-md-12">
						Country:
						<br/>
						<b>{{ $user->country }}</b>
					</div>
				</div>	
				<div class="row infoRow">
					<div class="col-md-12">
						City:
						<br/>
						<b>{{ $user->city }}</b>
					</div>
				</div>	
				<div class="row infoRow">
					<div class="col-md-12">
						Referral Code:
						<br/>
						<b>{{ isset($user->ref_code) && $user->ref_code != ""?$user->ref_code:"-" }}</b>
					</div>
				</div>	
			</div>
		</div>
	</div>
</div>