<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        We have just received a request for {{ $accType }} Account. The request details are:
        <br/><br/>
        Name: {{$name}}
        <br/>
        Email: {{$email}}
        <br/>
        Contact: {{$contact}}
        <br/>
        @if($forEnterprise == 1)
	        Organization Name: {{$orgName}}
	        <br/>
	        User Account(s) Required: {{$userCount}}
	        <br/>
        @endif        
        Space Required: {{$spaceGb}} GB(s)
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>