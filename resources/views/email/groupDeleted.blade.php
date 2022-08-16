<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        @if(isset($deletedByUserName) && $deletedByUserName != "")
        	{{$deletedByUserName}}({{$deletedByUserEmail}}) has deleted the group <b>{{$groupName}}</b>.
        @else
        	The group <b>{{$groupName}}</b> has been deleted.
        @endif
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