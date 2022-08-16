<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        @if($isOrg)
        	You have used up {{ $usedPercent }}% of your {{ $allottedQuotaStr }} quota for {{ $orgName }} Profile.
        @else
        	You have used up {{ $usedPercent }}% of your {{ $allottedQuotaStr }} quota for Retail Profile. 
        @endif
        <br/><br/>
        Please free some space or get more space allotted from the admin/hepldesk. 
        <br/><br/>
        @if($usedPercent < 100)
        	Please note, once you reach 100%, you will not be able to save new notes or receive any notes sent to you by others. 
        @else
        	Please note, you will not be able to save new notes or receive any notes sent to you by others till this is corrected.
        @endif
        <br/><br/>
        Please contact us on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a> if you need any assistance or you feel there is an error in this notification.
        <br/><br/>
        Thank you,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>