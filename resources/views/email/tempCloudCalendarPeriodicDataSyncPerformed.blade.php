<div class="row">
    <div class="col-md-12">
        Dear {{$name}} ({{$email}}),
        <br/><br/>
        Your {{ $cloudCalendarTypeName }} has been synced with syncToken : {{$syncToken}}.
        <br/><br/>
        Data that was set is:
        <br/><br/>
        last_sync_performed_at : {{$appuserCloudCalendarToken->last_sync_performed_at}}
        <br/>
        next_sync_due_at : {{$appuserCloudCalendarToken->next_sync_due_at}}
        <br/>
        sync_token : {{$appuserCloudCalendarToken->sync_token}}
        <br/><br/>
        <b>API RESPONSE:</b>
        <br/><br/>
        Regards,
        <br/><br/>
        Team HyLyt
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>