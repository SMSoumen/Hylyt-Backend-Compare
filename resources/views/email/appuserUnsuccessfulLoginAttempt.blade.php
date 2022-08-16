<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        @if(isset($deviceModelName) && $deviceModelName != "")
            There was an unsuccessful login attempt for {!! $systemName !!} from {{ $deviceModelName }} having {{ $sessionType }}.
        @else 
            There was an unsuccessful login attempt for {!! $systemName !!} from {{ $sessionType }}.
        @endif
        <br/><br/>
       <!--  @if(isset($deviceModelName) && $deviceModelName != "")
            Details of which are:
            <br/>
            Model: {{ $deviceModelName }} 
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
        @endif -->
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>