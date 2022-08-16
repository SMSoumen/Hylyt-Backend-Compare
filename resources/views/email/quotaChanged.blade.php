<?php
	$newAllottedKb = $userConstant->attachment_kb_allotted;
	$changeText = "increased";
	if($oldAllottedKb > $newAllottedKb)
		$changeText = "decreased";
		
	$newAllottedMb = round($newAllottedKb/1024);
?>
<div class="row">
    <div class="col-md-12">
        Hello {{$name}},
        <br/><br/>
        Your Cloud storage quota has been {{$changeText}} to {{$newAllottedMb}} MB.
        <br/><br/>
        Regards,
        <br/><br/>
        @if(isset($orgName) && $orgName != "")
            {!! $orgName !!}
        @else
            Team {!! $systemName !!}
        @endif
        @if(isset($orgLogoHtml) && $orgLogoHtml != "")
            {!! $orgLogoHtml !!}
        @else
            {!! $systemLogoHtml !!}
        @endif
        {!! $disclaimerHtml !!}
    </div>
</div>