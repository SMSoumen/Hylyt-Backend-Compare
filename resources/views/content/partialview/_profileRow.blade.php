<li class="optProfile" onclick="selectProfile('{{ $orgId }}', {{ $orgStatus }}, '{{ $orgName }}', '{{ $orgUsername }}', '{{ $orgUserEmail }}', '{{ $orgUrl }}');">
	<a>
		<div class="row">
			<div class="col-md-3">
				@if($orgUrl != "")
					<img src="{{ $orgUrl }}" style="max-height:25px;"/>
				@endif
			</div>
			<div class="col-md-8">
				<b>{{ $orgName }}</b>
				<br/>
				<i>{{ $orgUsername }}</i>
				<br/>
				{{ $orgMetrics }}
			</div>
			<div class="col-md-1">
				@if($orgIsSelected == 1)
					<i class="fa fa-2x fa-check"></i>
				@endif
			</div>
		</div>
	</a>
</li>