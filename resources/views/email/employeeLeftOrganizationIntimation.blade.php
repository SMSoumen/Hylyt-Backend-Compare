<div class="row">
    <div class="col-md-12">
        Hello,
        <br/><br/>
        An employee has just left the Organization ({{ $orgName }}). The user details are:
        <br/><br/>
        Name: {{$empNo}} - {{$name}}
        <br/>
        Email: {{$email}}
        <br/>
        Contact: {{$contact}}
        <br/>
        Department: {{$department}}
        <br/>
        Designation: {{$designation}}
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