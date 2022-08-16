<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        Your HyLyt Account was just used to sign in.
        <br/><br/>
        Any other signed in devices would be logged out and not be able to sync anymore.
        <br/><br/>
		We take security very seriously and we want to keep you in the loop on important actions in your account.
		<br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>