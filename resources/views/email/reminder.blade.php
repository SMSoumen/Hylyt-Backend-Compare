<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        You set a Reminder for task:
        <br/><br/>
        <blockquote>
            <b><i>{!! $reminderText !!}</i></b>
        </blockquote>
        <br/><br/>
        @if($combinedFromDateStr != "" && $combinedToDateStr != "")
            From: {{ $combinedFromDateStr }}
            <br/><br/>
            To: {{ $combinedToDateStr }}
            <br/><br/>
        @elseif($combinedFromDateStr != "")
            At: {{ $combinedFromDateStr }}
            <br/><br/>
        @endif
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