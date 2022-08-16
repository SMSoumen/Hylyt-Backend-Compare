<div class="row">
    <div class="col-md-12">
        <?php 
        $systemHelpEmail = Config::get('app_config_mail.system_help_email');
        ?>
        Dear {{$name}},
        <br/><br/>
        We are sorry to see you leave. As a reminder of the great memories you have made using {!! $systemName !!}, we have put together some data showing how much time you spent using it and how your work got easier and efficient.
        <br/><br/>
        Email: {{ $user->email }}<br/>
        @if(isset($user->contact) && $user->contact != "")
            Contact: {{ $user->contact }}<br/>
        @endif
        @if(isset($user->gender) && $user->gender != "")
            Gender: {{ $user->gender }}<br/>
        @endif
        @if(isset($user->city) && $user->city != "")
            City: {{ $user->city }}<br/>
        @endif
        @if(isset($user->country) && $user->country != "")
            Country: {{ $user->country }}<br/>
        @endif
        <?php 
        if($user->note_count > 0)
        {?>
        	Total Notes Created: {{ $user->note_count }}<br/>		
		<?php 
		}?>
		Total Days Used: {{ $user->day_count }}<br/>
        Reminders: {{ $user->total_r }}<br/>
        Notes (Archive): {{ $user->total_a }}<br/>
        Calendar events: {{ $user->total_c }}<br/>
        <?php 
        if($user->data_size_kb > 0)
        {?>
            Data Size: {{ $user->data_size_kb }}<br/>       
        <?php 
        }?>
        <?php 
        if($user->attachment_size_kb > 0)
        {?>
            Attachment Size: {{ $user->attachment_size_kb }}<br/>       
        <?php 
        }?>
        <br/><br/>
        To ensure any shortcomings faced by you are not experienced by other users, please provide your valuable feedback, complaint or suggestions to make {!! $systemName !!} even better. We hope you will be back soon.		
        <br/><br/>
        Please e-mail us your feedback or leave your suggestions on <a href="mailto:{{$systemHelpEmail}}">{{$systemHelpEmail}}</a>.
        <br/><br/>
        Hope to See you Soon!
        <br/><br/>
        Regards,
        <br/><br/>
        Team {!! $systemName !!}
        {!! $systemLogoHtml !!}
        {!! $disclaimerHtml !!}
    </div>
</div>