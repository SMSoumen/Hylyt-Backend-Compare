<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        @if(isset($deviceModelName) && $deviceModelName != "")
            You've just logged into {{ isset($appKeyMapping) ? $appKeyMapping->app_name : 'HyLyt' }} on {{ $deviceModelName }} having {{ $sessionType }}.
        @else
            You've just logged into {{ isset($appKeyMapping) ? $appKeyMapping->app_name : 'HyLyt' }} on {{ $sessionType }}.
        @endif
        <br/><br/>
        @if((isset($deviceModelName) && $deviceModelName != "") || (isset($ipAddress) && $ipAddress != ""))
            Details of which are:
            @if(isset($deviceModelName) && $deviceModelName != "")
                <br/>
                Model: {{ $deviceModelName }} 
            @endif
            @if(isset($deviceUniqueId) && $deviceUniqueId != "")
                <br/>
                Device: {{ $deviceUniqueId }} 
            @endif
            @if(isset($ipAddress) && $ipAddress != "")
                <br/>
                IP Address: {{ $ipAddress }} 
            @endif
            @if(isset($clientDetails) && $clientDetails != "")
                <br/>
                {{ $clientDetails }} 
            @endif
            <br/><br/>
        @endif
        Regards,
        <br/><br/>
        Team {{ isset($appKeyMapping) ? $appKeyMapping->app_name : 'HyLyt' }}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>