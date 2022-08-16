<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        We at {{ $orgName }}(HyLyt) value your inputs.
        <br/><br/>
        Your submitted contact report is:
        <br/><br/>
        {{$contactReport}}
        <br/><br/>
        Thank You for sparing some of your valuable time and contacting the admin.
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