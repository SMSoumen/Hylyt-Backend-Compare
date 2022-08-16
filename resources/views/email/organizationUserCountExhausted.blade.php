<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Hello,
        <br/><br/>
        Your organization "{{ $orgName }}" has been allotted {{ $allottedUserCount }} user account(s).
        <br/><br/>
        This quota has been exhausted now. Please upgrade and get more users added if you need. Contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> to process your request or address any queries or concerns you have.
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>