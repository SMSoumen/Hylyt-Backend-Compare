<div class="row">
    <div class="col-md-12">
        Dear {{$name}},
        <br/><br/>
        {!! $notifText !!}
        <br/><br/>
        @if(isset($notifImageUrl) && $notifImageUrl != "")
            <img src="{{ $notifImageUrl }}" alt="image" width="1000" />
            <br/><br/>
        @endif
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>