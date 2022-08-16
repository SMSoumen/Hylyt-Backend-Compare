<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        A new user with the following credentials has signed up and joined {{ $orgName }} using the auto enroll option.
        <br/><br/>
		Name: {{ $empName }}
		<br/>
		Email: {{ $empEmail }}
		<br/>
		Contact: {{ $empContact }}
 		<br/><br/>
		Please add them to the relevant group(s) and also give the required permissions to access content or to perform actions.
        <br/><br/> 
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>