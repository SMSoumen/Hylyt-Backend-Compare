<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        You have received a new {{ $contentTypeText }} from {{ $senderDetailsStr }}. The details are:
        <br/><br/>
        {!! $contentText !!}
        @if($combinedFromDateStr != "" && $combinedToDateStr != "")
            <br/><br/>
            From: {{ $combinedFromDateStr }}
            <br/><br/>
            To: {{ $combinedToDateStr }}
            <br/><br/>
        @elseif($combinedFromDateStr != "")
            <br/><br/>
            For: {{ $combinedFromDateStr}}
            <br/><br/>
        @endif
        <br/><br/>
        @if(isset($remContentUrl) && $remContentUrl != '')
        	If you do not wish to retain this, you can delete it content from your account by clicking on this <a href="{{ $remContentUrl }}">link</a>.
        	<br/><br/>
        @endif
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