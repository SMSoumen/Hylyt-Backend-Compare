<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        A new member has just joined the open group <b>{{$groupName}}</b> that is being managed by you.
        <br/><br/>
        Name: {{$name}}
        <br/>
        Email: {{ $email }}
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