<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        Welcome on board!!
        <br/><br/>
        You have successfully joined "{{ $orgName }}".
        <br/><br/>
        Please feel free to contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> in case of any queries, feedbacks or suggestions.
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