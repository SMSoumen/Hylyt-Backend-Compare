<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Greetings from HyLyt!!!
        <br/><br/>
        You have not yet verified your email so as to continue logging into the system. You can perform an email verification from the app by trying to log in.
        <br/><br/>
        You can also verify your account using this <a href="{{$verifyLink}}">link</a>. You can then log in with only your email ID and password.
        <br/><br/>
        HyLyt will enable you to manage all important Digital content on the go.
        <br/><br/>
        We request you to complete the verfication, check out SocioRAC soon and discover a new way to Retain/Save/Share and Manage Digital content which saves time and makes you more efficient.
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
        <br/><br/><br/><br/>
        If you do not want to receive these emails, <a href="{{$unsubLink}}">Unsubscribe</a>.
    </div>
</div>