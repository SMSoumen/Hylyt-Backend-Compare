<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        We at {{ $orgName }}(HyLyt) value your report on abuse.
        <br/><br/>
        Your submitted abuse report is:
        <br/><br/>
        {{$abuseReport}}
        <br/><br/>
        Thank You for sparing some of your valuable time and reporting the abuse.
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