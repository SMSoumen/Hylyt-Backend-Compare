<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        Following users have been inactive over the past week.
        <br/><br/>
        @if (count($userList) > 0)
        	<table width="100%" border="1">
        		<tr>
        			<th>Sr No</th>
        			<th>Name</th>
        			<th>Email</th>
        			<th>Phone</th>
        		</tr>
        		@php
				    $i = 1;
				@endphp
			    @foreach ($userList as $user)
				    <tr>
				    	<td>{{ $i++ }}</td>
				    	<td>{{ $user->fullname }}</td>
				    	<td>{{ $user->email }}</td>
				    	<td>{{ $user->contact }}</td>
				    </tr>
				@endforeach
			</table>
		@endif
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>