<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Hello {{$name}},
        <br/><br/>
        Thank you for signing up.
        <br/><br/>
        Please complete the verification process by entering the verification code {{$verCode}}. In case you've exit from email verification screen, re-login to perform verification.
        <br/><br/>
        You can also verify your account using this <a href="{{$verifyLink}}">link</a>.
        <br/><br/>
        Please feel free to contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> in case of any queries, feedbacks or suggestions.
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>