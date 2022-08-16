<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Your subscription for business account will soon expire in {{ $laterDayCount }} day(s).
        <br/><br/>
        Make sure you renew the subscription soon.
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>