<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        {{ $confStartStr }}
        <br/><br/>
        {!! $contentText !!}
        @if($utcFromDateStr != "" && $utcToDateStr != "")
            <br/><br/>
            From: {{ $utcFromDateStr . '(UTC)' }} OR {{ $istFromDateStr . '(IST)' }}
            <br/><br/>
            To: {{ $utcToDateStr . '(UTC)' }} OR {{ $istToDateStr . '(IST)' }}
            <br/><br/>
        @endif
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