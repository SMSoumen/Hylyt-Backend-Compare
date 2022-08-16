<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        Your {{ $orgName }}(HyLyt) profile content has been restored.
        @if(isset($restoreDataMetrics))
            <br/><br/>
            <b>Restored Content Count: </b> {{ $restoreDataMetrics['restoreContentCount'] }}
            <br/>
            <b>Restored Content Size: </b> {{ $restoreDataMetrics['restoreContentSizeStr'] != '' ? $restoreDataMetrics['restoreContentSizeStr'] : '-' }}
            <br/><br/>
        @endif
        <br/><br/>
        For further details you can contact your system admin(s).
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