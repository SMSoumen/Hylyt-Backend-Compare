<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        Welcome to {!! $systemName !!}!
        <br/><br/>
        We are happy to have you on board. {!! $systemName !!} allows you to SAVE content instantly from any app/ screen, interconnect it through TAGS, SEARCH it instantly when needed and SHARE it securely with controls. Its chatting, reminders, note taking, video conference and information management rolled into one. {!! $systemName !!} aims at providing complete user satisfaction and a hassle-free experience. For further information, please visit us at <a href="https://hylyt.co">hylyt.co</a>.
        <br/><br/>
        Please feel free to contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> in case of any queries, feedbacks or suggestions.
        <br/><br/>
        @if($showAccountDeactivationStr)
        As you've signed up for trail, the validity is of 14 days and then your account will be deactivated.
        <br/><br/>
        @endif
        Detailed instructions to set up {!! $systemName !!} for use and how to use can be found here for <a href="{{ Config::get('app_config.retailUserMobileAndroidHowToLink') }}">Android</a>, <a href="{{ Config::get('app_config.retailUserMobileIosHowToLink') }}">iOS</a> and <a href="{{ Config::get('app_config.retailUserWebHowToLink') }}">Web</a>.
		<br/><br/>
        We are sure, very soon you will wonder how you managed without {!! $systemName !!}! It’s time to HyLyt your business and make you powerful, productive and profitable!!
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>