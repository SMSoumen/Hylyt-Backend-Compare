<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        $androidAppLink = Config::get('app_config.androidAppLink');
        $iosAppLink = Config::get('app_config.iosAppLink');
        ?>
        Hello,
        <br/><br/>
        {{$senderName}} ({{$senderEmail}}) has invited you to use HyLyt.
        <br/><br/>
        HyLyt is a multi-platform information management and sharing application which allows you to SAVE content with 1-click in the form of a Reminder, Archive or Calendar Entry. It even helps you to print it and share it amongst different Social Media Platforms.
        <br/><br/>
        HyLyt aims at providing complete user satisfaction and a hassle-free experience. For further information about the application, please visit <a href="www.sociorac.com">www.sociorac.com</a>.
        <br/><br/>
        Please feel free to contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> in case of any queries, feedbacks or suggestions.
        <br/><br/>
        Details of the features of HyLyt and how to use can be found <a href="{{ Config::get('app_config.retailUserHowToLink') }}">here</a>.
        <br/><br/>
        {{$senderName}} is enjoying using HyLyt and wants to share content with you on HyLyt and enjoy its features too. We are sure, very soon you too will wonder how you managed without HyLyt!
        <br/><br/>
        HyLyt Android App can be downloaded <a href="{{$androidAppLink}}">here</a>.
        <br/><br/>
        HyLyt iOS App can be downloaded <a href="{{$iosAppLink}}">here</a>.
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>