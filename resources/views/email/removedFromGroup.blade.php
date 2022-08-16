<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        You have been removed from group <b>{{$groupName}}</b>
        @if(isset($addedByName) && $addedByName != "")
        	by {{$addedByName}}({{$addedByEmail}})
        @endif
        .<br/><br/>
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