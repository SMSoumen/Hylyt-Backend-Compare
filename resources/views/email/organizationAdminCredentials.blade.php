<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Welcome on board!!
        <br/><br/>
        You have been added to "{{ $orgName }}" as an admin.
        <br/><br/>
        Please login to the management console from your PC/ Laptop by clicking on {{ $mgmtSysLink }} and using the following credentials.
        <br/><br/>
        Email: {{ $email }}
        <br/>
        Code: {{ $orgCode }}
        <br/>
        Password: {{ $password }}
        <br/><br/>
        Detailed instructions on setting up the Admin backend can be found <a href="{{ Config::get('app_config.enterpAdminSetupLink') }}">here</a>.
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>