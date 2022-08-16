@php
	$assetBasePath = Config::get('app_config.assetBasePath');
	$baseIconThemeUrl = url($assetBasePath.Config::get('app_config_asset.appWebIconThemePath')).'/';

	$cloudMessageCreateIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageMessageCreate');
	$cloudMessageDeleteIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageMessageDelete');
	$cloudMessageMessageViewIconPath = $baseIconThemeUrl.Config::get('app_config_asset.appIconCloudStorageMessageView');
@endphp

@php
	$noAttachmentsFound = true;

	$messageCount = 0;
	$messageList = array();

	$breadcrumbArr = array();

	if(isset($messageResponse) && isset($messageResponse['messageCount']))
	{
		$messageCount = $messageResponse['messageCount'];
		$messageList = $messageResponse['messageList'];
	}


@endphp

<style type="text/css">
	.noSearchStrFoundDiv {
		height: 150px;
		vertical-align: middle;
		text-align: center;
		font-size: 30px;
	}
</style>

@if($isPrimaryListLoad)
	<div class="row" style="margin-left: 0px; margin-right: 0px">
		@if($hideOperations == 0)
			<button type="button" class="btn btn-default btn-sm" onclick="loadCloudMailBoxMessageDetailsDialog('{{ $orgKey }}', '{{ $cloudMailBoxTypeCode }}');" data-toggle="tooltip" title="View Message">
	    		<i class="fa fa-eye"></i>
	    	</button>
	    @endif
		<div class="col-md-8">
			<i class="fa fa-arrow-left" style="cursor: pointer;" onclick="loadAttachmentListSubView('{{ $rightMostPath }}', '', '{{ $rightMostBaseMessageType }}')"></i>
			<span class="ca-current-message-name">{{ $currentMessageName }}</span>
		</div>
		<div class="col-md-4" align="right">
			<div class="row">
		    </div>
		</div>
	</div>
	<div class="row" style="margin-left: 0px; margin-right: 0px; display: none; margin-bottom: 10px;" id="divAddCloudMessageView">
		<div class="col-md-9">
			<div class="input-group input-group-sm">
				<input type="text" id="txtNewCloudMessageName" class="form-control input-sm" placeholder="Message Name" autocomplete="off" value="" >
				<span class="input-group-btn">
					<button class="btn btn-success btn-sm" type="button" onclick="performAddNewCloudMessage();"><i class="fa fa-save"></i></button>
					<button class="btn btn-danger btn-sm" type="button" onclick="cancelAddNewCloudMessage(false);"><i class="fa fa-times"></i></button>
				</span>
			</div>
		</div>
	</div>
	<div class="row" style="margin-left: 0px; margin-right: 0px; display: none; margin-bottom: 10px;" id="divAddCloudMessageView">
		<div class="col-md-9">
			<div class="input-group input-group-sm">
				<input type="message" id="inpNewCloudMessage" class="form-control input-sm" placeholder="Select Message" autocomplete="off" value="" >
				<span class="input-group-btn">
					<button class="btn btn-success btn-sm" type="button" onclick="performAddNewCloudMessage();"><i class="fa fa-save"></i></button>
					<button class="btn btn-danger btn-sm" type="button" onclick="cancelAddNewCloudMessage(false);"><i class="fa fa-times"></i></button>
				</span>
			</div>
		</div>
	</div>
@endif

@if(isset($messageCount) && ($messageCount) > 0)
	<div class="row" style="margin-left: 0px; margin-right: 0px">
		<div class="col-md-12">
			@if(count($messageList) > 0)
				@foreach($messageList as $messageIndex => $messageObj)
					@php
						$messageId = $messageObj['messageId'];
						$messageSnippet = $messageObj['snippet'];
						$mailDateTs = $messageObj['mailDateTs'];
						$cloudMailBoxTypeCode = $messageObj['cloudMailBoxTypeCode'];
						$mailSubject = $messageObj['mailSubject'];
						$senderOrReceiverEmail = $messageObj['senderOrReceiverEmail'];
						$mailContainerLabel = $messageObj['mailContainerLabel'];
						$isUnread = $messageObj['isUnread'];
						$isReceived = $messageObj['isReceived'];
						$isSent = $messageObj['isSent'];

						$mailDateStr = dbToDispDateTimeWithTZ($mailDateTs, $tzStr);
					@endphp
					<div class="row ca-message-row">
						<div class="col-md-11">
							<div class="row">
								<div class="col-md-10">
									<span class="ca-message-subject"><b>{{ $mailSubject }}</b></span>
									<br/>
									<span class="ca-message-email">[{{ $mailContainerLabel }}] - {{ $senderOrReceiverEmail }}</span>
								</div>
								<div class="col-md-2" align="right">
									<span class="ca-message-time">{{ $mailDateStr }}</span>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span class="ca-message-snippet">{{ $messageSnippet }}</span>
								</div>
							</div>
						</div>
						<div class="col-md-1 ca-message-actions" align="right" style="vertical-align: middle;">
							@if($hideOperations == 0)
								<button type="button" class="btn btn-default btn-sm" onclick="loadCloudMailBoxMessageDetailsDialog('{{ $orgKey }}', '{{ $cloudMailBoxTypeCode }}', '{{ $messageId }}');" data-toggle="tooltip" title="View Message">
						    		<i class="fa fa-eye"></i>
						    	</button>
								<button type="button" class="btn btn-default btn-sm" onclick="performDeleteCloudMailBoxMessage('{{ $orgKey }}', '{{ $cloudMailBoxTypeCode }}', '{{ $messageId }}');" data-toggle="tooltip" title="Delete Message">
						    		<i class="fa fa-trash"></i>
						    	</button>
						    @endif
						</div>
					</div>
				@endforeach
			@endif
		</div>
	</div>
@endif