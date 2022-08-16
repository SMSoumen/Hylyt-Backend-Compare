<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        Following are the analytics for {{ $orgName }}: 
        <br/><br/>
        {{ $startDate }} - {{ $endDate }}
        <br/>
		<div class="row" align="center">
			<b>User(s)</b>
		</div>
		<br/>
        <br/><br/>
        @if (count($compiledResults) > 0)
        	<table width="100%" border="1">
        		@php
				    $rowSpan = 1;
				    $colspan = 1;
				@endphp
        		@if($isRetail)
        			@php
					    $rowSpan = 2;
				    	$colspan = 2;
					@endphp
				@endif
        		<tr>
        			<th rowspan="{{ $rowSpan }}">Sr No</th>
        			<th rowspan="{{ $rowSpan }}">Name</th>
        			<th rowspan="{{ $rowSpan }}">Email</th>
        			<th rowspan="{{ $rowSpan }}">Phone</th>
        			<th colspan="{{ $colspan }}">Contents Created</th>
        			<th colspan="{{ $colspan }}">Contents Updated</th>
        			<th colspan="{{ $colspan }}">Total</th>
        			<th colspan="{{ $colspan }}">Quota Used</th>
        		</tr>
        		@if($isRetail)
	        		<tr>
	        			<th>Folder</th>
	        			<th>Group</th>
	        			<th>Folder</th>
	        			<th>Group</th>
	        			<th>Folder</th>
	        			<th>Group</th>
	        			<th>Folder</th>
	        			<th>Group</th>
	        		</tr>
        		@endif
        		@php
				    $i = 1;
				@endphp
			    @foreach ($compiledResults as $res)
				    <tr>
				    	<td>{{ $i++ }}</td>
				    	<td>{{ $res['name'] }}</td>
				    	<td>{{ $res['email'] }}</td>
				    	<td>{{ $res['contact'] }}</td>
        				@if($isRetail)
					    	<td>{{ $res['contentCreated'] }}</td>
					    	<td>{{ $res['grpContentCreated'] }}</td>
					    	
					    	<td>{{ $res['contentUpdated'] }}</td>
					    	<td>{{ $res['grpContentUpdated'] }}</td>
					    	
					    	<td>{{ $res['totalContentCount'] }}</td>
					    	<td>{{ $res['totalGrpContentCount'] }}</td>
					    	
					    	<td>{{ $res['quotaUsed'] }}</td>
					    	<td>{{ $res['grpQuotaUsed'] }}</td>
        				@else
					    	<td>{{ $res['contentCreated'] }}</td>
					    	<td>{{ $res['contentUpdated'] }}</td>
					    	<td>{{ $res['totalContentCount'] }}</td>
					    	<td>{{ $res['quotaUsed'] }}</td>
        				@endif
				    </tr>
				@endforeach
			</table>
		@else
			<div class="row" align="center">
				<b>No user(s) added yet.</b>
			</div>
		@endif
        <br/>
        @if(isset($compiledGroupResults))
			<div class="row" align="center">
				<b>Group(s)</b>
			</div>
			<br/>
	        @if (count($compiledGroupResults) > 0)
	        	<table width="100%" border="1">
	        		<tr>
	        			<th>Sr No</th>
	        			<th>Name</th>
	        			<th>Contents Created</th>
	        			<th>Contents Updated</th>
        				<th>Total</th>
	        			<th>Quota Used</th>
	        		</tr>
	        		@php
					    $i = 1;
					@endphp
				    @foreach ($compiledGroupResults as $res)
					    <tr>
					    	<td>{{ $i++ }}</td>
					    	<td>{{ $res['name'] }}</td>
					    	<td>{{ $res['contentCreated'] }}</td>
					    	<td>{{ $res['contentUpdated'] }}</td>
				    		<td>{{ $res['totalContentCount'] }}</td>
					    	<td>{{ $res['quotaUsed'] }}</td>
					    </tr>
					@endforeach
				</table>
			@else
				<div class="row" align="center">
					<b>No group(s) added yet.</b>
				</div>
			@endif
		@endif
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>