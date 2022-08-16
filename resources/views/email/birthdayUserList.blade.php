<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        Following users from your organization {{ $orgName }} have their birthday today.
        <br/><br/>
        @if (count($userList) > 0)
        	<table width="100%" border="1">
        		<tr>
        			<th>Sr No</th>
        			<th>ID</th>
        			<th>Name</th>
        			<th>Email</th>
        			<th>Phone</th>
        			<th>Date Of Birth</th>
        		</tr>
        		@php
				    $i = 1;
				@endphp
			    @foreach ($userList as $user)
				    <tr>
				    	<td>{{ $i++ }}</td>
				    	<td>{{ $user->employee_no }}</td>
				    	<td>{{ $user->employee_name }}</td>
				    	<td>{{ $user->email }}</td>
				    	<td>{{ $user->contact }}</td>
				    	<td>{{ dbToDispDate($user->birthdt) }}</td>
				    </tr>
				@endforeach
			</table>
		@endif
        <br/><br/>
        Regards,
        <br/><br/>
        @if(isset($orgName) && $orgName != "")
            {!! $orgName !!}
        @else
            Team HyLyt
        @endif
        @if(isset($orgLogoHtml) && $orgLogoHtml != "")
            {!! $orgLogoHtml !!}
        @else
            {!! $systemLogoHtml !!}
        @endif
        {!! $disclaimerHtml !!}
    </div>
</div>