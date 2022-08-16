<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Your App lock Pin is {{$pin}}.
        <br/><br/>
       	We request you to set a new Pin by disabling and re-enabling the Application lock pin.
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>