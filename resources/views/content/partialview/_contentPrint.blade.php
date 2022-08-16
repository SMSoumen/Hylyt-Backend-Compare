<style>	
</style>
@php
	$canPrintSource = FALSE;
	$canPrintFolder = FALSE;
	$canPrintTag = FALSE;
	$canPrintContent = FALSE;
	$canPrintCreateDate = FALSE;
	$canPrintEventDate = FALSE;

    $PRINT_SOURCE_ID = 1;
    $PRINT_FOLDER_ID = 2;
    $PRINT_TAG_ID = 3;
    $PRINT_CONTENT_ID = 4;
    $PRINT_EVENT_DATE_ID = 5;
    $PRINT_CREATE_DATE_ID = 6;
    if(isset($printFields) && is_array($printFields))
    {
		if(in_array($PRINT_SOURCE_ID, $printFields))
			$canPrintSource = TRUE;
		if(in_array($PRINT_FOLDER_ID, $printFields))
			$canPrintFolder = TRUE;
		if(in_array($PRINT_TAG_ID, $printFields))
			$canPrintTag = TRUE;
		if(in_array($PRINT_CONTENT_ID, $printFields))
			$canPrintContent = TRUE;
		if(in_array($PRINT_EVENT_DATE_ID, $printFields))
			$canPrintEventDate = TRUE;
		if(in_array($PRINT_CREATE_DATE_ID, $printFields))
			$canPrintCreateDate = TRUE;
	}
	$groupOrFolderTitle = "Group";
	if($isFolder)
		$groupOrFolderTitle = "Folder";
@endphp
@if(isset($contents) && count($contents) > 0)
	<table width="100%">
		<tr>
			<th colspan="2" style="font-size: 18px;text-align: center;">
				{{ Config::get('app_config.system_name') }}
			</th>
		</tr>
		@foreach($contents as $content)
			@php
				$sourceName = $content['source'];
				$groupOrFolderName = $content['groupOrFolder'];
				$tagStr = $content['tag'];
				$contentTitle = $content['title'];
				$contentText = $content['content'];
				$contentCreateDt = $content['createDt'];
				$contentEventDt = $content['eventDt'];
                $formContentObj = $content['formContentObj'];

                $isConversation = $formContentObj['isConversation'];

                $conversationArr = NULL;
                if($isConversation == 1 && isset($formContentObj['contentConversationResponse']) && isset($formContentObj['contentConversationResponse']['conversation']))
                {
                	$conversationArr = $formContentObj['contentConversationResponse']['conversation'];
                }
			@endphp
			@if($canPrintSource)
				<tr>
					<th width="15%">Source: </th>
					<td>{{ $sourceName }}</td>
				</tr>
			@endif
			@if($canPrintFolder)
				<tr>
					<th>{{ $groupOrFolderTitle }}: </th>
					<td>{{ $groupOrFolderName }}</td>
				</tr>
			@endif
			@if($canPrintTag)
				<tr>
					<th>Tag(s): </th>
					<td>{{ $tagStr }}</td>
				</tr>
			@endif

			@if($canPrintContent)
				<tr>
					<th valign="top">Title</th>
					<td>{!! $contentTitle !!}</td>
				</tr>
			@endif

			@if($canPrintContent)
				<tr>
					<th valign="top">Content: </th>
					<td>
						@if($isConversation == 1 && isset($conversationArr))
							@php
							$totalConversationCount = isset($conversationArr) ? count($conversationArr) : 0;
        					$conversationArrReversed = array_reverse($conversationArr);
							@endphp
							@foreach($conversationArrReversed as $i => $conversationObj)
								@php
								$senderStr = $conversationObj['sender'];
								$sentAtTs = $conversationObj['sentAt'];
								$baseConvContentStr = $conversationObj['content'];
								$baseConvContentStrStripped = $conversationObj['contentStripped'];
								$decodedConvContentStr = ($baseConvContentStrStripped);
								$isForwarded = $conversationObj['isForwarded'];
								$isEdited = $conversationObj['isEdited'];
								$isDeleted = $conversationObj['isDeleted'];
								$hasReply = $conversationObj['hasReply'];
								$isUserMsgSender = $conversationObj['isUserMsgSender'];
								$alignmentForMsg = $isUserMsgSender == 1 ? 'right' : 'left';
								$consConvIndex = ($totalConversationCount - 1) - $i;
								@endphp
								<div>
									<b>{{ $senderStr }}</b>
									@if($hasReply == 1)
										@php
										$replySenderStr = $conversationObj['replySender'];
										$replyConvContentStr = $conversationObj['replyContent'];
										@endphp
										<br/>
										&nbsp &nbsp &nbsp
										{{ $replySenderStr }}
										<br/>
										&nbsp &nbsp &nbsp
										<i>{{ $replyConvContentStr }}</i>
									@endif
									<br/>
									@if($isDeleted == 1)
										<b><i>{{ $baseConvContentStr }}</i></b>
									@else
										<b>{{ $baseConvContentStr }}</b>
									@endif

									@if($isDeleted == 1 || $isEdited == 1)
										@php
										$editedOrDeletedByStr = $conversationObj['editedOrDeletedBy'];
										$editedOrDeletedConvContentStr = $conversationObj['editOrDeleteStr'];
										$editedOrDeletedAtTs = $conversationObj['editedOrDeletedAt'];
										if($isDeleted)
										{
											$editedOrDeletedActionStr = 'Deleted';												
										}
										else
										{
											$editedOrDeletedActionStr = 'Edited';
										}
										@endphp
										@if($isDeleted == 1)
											<br/>
											{!! $editedOrDeletedConvContentStr !!}
										@endif
										{{ $editedOrDeletedActionStr }} on {{ dbToDispDateTimeWithTZOffset($editedOrDeletedAtTs, $tzOfs) }} by {!! $editedOrDeletedByStr !!}
									@endif

									<br/>
 									{{ dbToDispDateTimeWithTZOffset($sentAtTs, $tzOfs) }}
 									<br/>
								</div>
							@endforeach
						@else
							{!! $contentText !!}
						@endif
					</td>
				</tr>
			@endif
			@if($canPrintCreateDate)
				<tr>
					<th>Created At: </th>
					<td>{{ $contentCreateDt }}</td>
				</tr>
			@endif
			@if($canPrintEventDate && $contentEventDt != "")
				<tr>
					<th valign="top">Event At: </th>
					<td>{!! $contentEventDt !!}</td>
				</tr>
			@endif
			<tr>
				<td><br/></td>
				<td><br/></td>
			</tr>
		@endforeach
	</table>
@endif