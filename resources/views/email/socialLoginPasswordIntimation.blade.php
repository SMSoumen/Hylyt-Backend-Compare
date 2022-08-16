<div class="row">
    <div class="col-md-12">
        Dear {{ $name }},
        <br/><br/>
        Thank you for signing up for {!! $systemName !!}!
        <br/><br/>
        We see you have signed up using social authentication. You can also choose to login using following credentials.
        <br/><br/>     
        Email: {{ $email }} 
        <br/>
        Password: {{ $genPassword }}
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>