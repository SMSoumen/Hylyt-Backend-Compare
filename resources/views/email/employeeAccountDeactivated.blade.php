<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        Your {{ $orgName }}(HyLyt) profile has been deactivated.
        <br/><br/>
        For further details you can contact your system admin(s).
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