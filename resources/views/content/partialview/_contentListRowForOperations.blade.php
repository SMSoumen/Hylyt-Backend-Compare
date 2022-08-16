<div class="row content-list-row" style="border-left: {{ $contentColorCode }} solid 8px !important;">
	<div class="content-list-row-1">
	    <div class="col-md-6">
	    	@if($contentIsMarked == 1)
				<img src="{{ $isMarkedIconPath }}" class="content-list-mark-icon" />
			@else
				<img src="{{ $isUnMarkedIconPath }}" class="content-list-mark-icon" />
			@endif

			@if(isset($showSelection) && $showSelection)
				<input type="checkbox" class="cbContent{{ $contentIsFolder }}" value="{{ $contentId }}"/>
			@endif
				
	    </div>
	    <div class="col-md-6 text-right">
	    	@if($contentHasAttachment == 1) 
				<object type="image/svg+xml" data="{{ $attachmentIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
			@endif
	    	@if($contentIsShareEnabled == 0)
				<object type="image/svg+xml" data="{{ $isRestrictedIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
			@endif
			@if($contentIsLocked == 1)
				<object type="image/svg+xml" data="{{ $isLockedIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
			@endif
			<span class="content-date-text">{{ dbToDispDateTimeWithTZ($contentModDate, $tzStr) }}</span>
	    </div>
	</div>

	<div class= "col-md-12 col-sm-12 content-text">
		{!! ($contentText) !!}
	</div>

	@if ($isAllNotes)
		<div class= "col-md-12 col-sm-12 content-text">
			<object type="image/svg+xml" data="{{ $contentBelongsToIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
			<span class="content-folder content-secondary-text">{{ $contentBelongsToName }}</span>
		</div>
	@endif

	@if ($contentReminderDateStr != "")
		<div class= "col-md-12 col-sm-12 content-text">
				<span class="content-start-date content-secondary-text">
					<object type="image/svg+xml" data="{{ $contentTypeIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
					{{ $contentReminderDateStr }}
				</span>
		</div>
	@endif

	@if ($contentSenderStr != "")
		<div class= "col-md-12 col-sm-12 content-text">
			<span class="content-sender content-secondary-text">
				<object type="image/svg+xml" data="{{ $contentSenderIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
				{{ $contentSenderStr }}
			</span>
		</div>
	@endif

	@if (count($contentTagArr) > 0)
		<div class= "col-md-12 col-sm-12 content-text">
			<object type="image/svg+xml" data="{{ $tagIconPath }}" class="content-list-thm-icon {{ $consTheme }}"></object>
			@foreach($contentTagArr as $tagName)
				<small class="label label-default">{!! $tagName !!}</small>
			@endforeach
		</div>
	@endif
</div>