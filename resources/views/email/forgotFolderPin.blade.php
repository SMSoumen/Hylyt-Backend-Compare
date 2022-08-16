<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Your Folder lock Pin is {{$pin}}.
        <br/><br/>
        We request you to set a new Pin by disabling and re-enabling the Folder lock pin.
        <br/><br/>
        Regards,
        <br/><br/>
        @if(isset($orgName) && $orgName != "")
            {!! $orgName !!}
        @else
            Team {!! $systemName !!}
        @endif
        @if(isset($orgLogoHtml) && $orgLogoHtml != "")
            {!! $orgLogoHtml !!}
        @else
            {!! $systemLogoHtml !!}
        @endif
        {!! $disclaimerHtml !!}
    </div>
</div>