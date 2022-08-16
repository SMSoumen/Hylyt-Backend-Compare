<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $androidAppLink = Config::get('app_config.androidAppLink');
        $iosAppLink = Config::get('app_config.iosAppLink');
        ?>
        Hello {{$name}},
        <br/><br/>
        Welcome on board!!
        <br/><br/>
        You have been added to "{{ $orgName }}" by your group admin. Please complete the On-boarding process by entering the Email ID ({{ $empEmail }}), Organization Code ({{ $orgCode }}) and verification code {{$verCode}} on the HyLyt app on your mobile. 
        <br/><br/>
        If you are an existing HyLyt user, please click <a href="{{ $enterpAccountAutoSetupLink }}">here</a> to connect you to "HyLyt By SocioRAC" Enterprise profile.
        <br/><br/>
        If you are not an existing HyLyt user – 
        <br/><br/>
        1.  Please click <a href="{{ $enterpAccountAutoSetupLink }}">here</a> to create your account and connect to the "HyLyt By SocioRAC" Enterprise profile.
        <br/><br/>
        2.  Download and install HyLyt Android app <a href="{{ $androidAppLink }}">here</a> or HyLyt iOS app <a href="{{ $iosAppLink }}">here</a>.
        <br/><br/>
        3.  Sign in using the details sent in the welcome mail to your mail id.
        <br/><br/>
        Detailed instruction on the features of HyLyt and how to use can be found here for <a href="{{ Config::get('app_config.retailUserMobileAndroidHowToLink') }}">Android</a>, <a href="{{ Config::get('app_config.retailUserMobileIosHowToLink') }}">iOS</a> and <a href="{{ Config::get('app_config.retailUserWebHowToLink') }}">Web</a>.
        <br/><br/>
        Regards,
        <br/><br/>
        <!-- Team HyLyt -->
        {!! $orgName !!}
        @if(isset($orgLogoHtml) && $orgLogoHtml != "")
            {!! $orgLogoHtml !!}
        @else
            {!! $systemLogoHtml !!}
        @endif
        {!! $disclaimerHtml !!}
    </div>
</div>