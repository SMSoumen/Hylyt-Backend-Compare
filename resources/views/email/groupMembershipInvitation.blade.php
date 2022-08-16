@php
    $androidAppLink = Config::get('app_config.androidAppLink');
    $iosAppLink = Config::get('app_config.iosAppLink');
    $webAppLinkForRegistration = Config::get('app_config.webAppLinkForRegistration');
    $webAppLinkForRegistration .= '?' . Config::get('app_config.webAppRegistrationAssistParamForName') . '=' . urlencode($recipientName);
    $webAppLinkForRegistration .= '&' . Config::get('app_config.webAppRegistrationAssistParamForEmail') . '=' . urlencode($recipientEmail);
@endphp
<div class="row">
    <div class="col-md-12">     
        Hello {{$name}},
        <br/><br/>
        You have been invited to join the HyLyt Group - {{ $groupName }}, by {{ $invitedByName }} ({{ $invitedByEmail }}).
        <br/><br/>
        If you are a HyLyt user, then join using this <a href="{{ $groupJoinLink }}">link</a>.
        <br/><br/>
        - OR - 
        <br/><br/>
        If you are not a HyLyt user, you can join HyLyt using the following links:
        <br/>
        Android: {{ $androidAppLink }}
        <br/>
        iOS: {{ $iosAppLink }}
        <br/>
        Web: {{ $webAppLinkForRegistration }}
        <br/><br/>
        Regards,
        <br/><br/>
        @if(isset($orgName) && $orgName != "")
            {!! $orgName !!}
        @else
            Team HyLyt
        @endif
        @if(isset($orgLogoHtml) && $orgLogoHtml != "")
            {!! $orgLogoHtml !!}
        @else
            {!! $systemLogoHtml !!}
        @endif
        {!! $disclaimerHtml !!}
    </div>
</div>