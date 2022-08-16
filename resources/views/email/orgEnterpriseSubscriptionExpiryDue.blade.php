<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Hello,
        <br/><br/>
        The Enterprise account subscription for your organization "{{ $orgName }}" will expire in {{ $laterDayCount }} day(s).
        <br/><br/>
        Please renew the subscription soon. Contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> to process your renewal or address any queries or concerns you have.
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>