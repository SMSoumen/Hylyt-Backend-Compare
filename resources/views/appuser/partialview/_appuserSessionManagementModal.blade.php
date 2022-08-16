<style>
    .sessionExhaustionMessageDiv 
    {
        margin-bottom: 16px;
        font-size: 14px;
        font-weight: 600;
    }

    .sessionRowDiv 
    {
        margin-bottom: 10px;
    }
</style>
<!-- Modal -->
<div class="modal fade noprint" id="appuserSessionManagementModal" tabindex="-1" role="dialog" aria-labelledby="divAppuserSessionManagementModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Session Management</h4>
            </div>
            <div class="modal-body">
                @if($userIsLoggedIn == 0)
                    <div class="row">
                        <div class="col-md-12 sessionExhaustionMessageDiv">
                            Available sessions exhausted. Please remove an existing session to continue logging in.
                        </div> 
                    </div>
                @endif

                @if(isset($userSessions) && is_array($userSessions) && count($userSessions) > 0)
                    @foreach($userSessions as $userSessionObj)
                        @php
                        $sessionId = $userSessionObj['id'];
                        $sessionType = $userSessionObj['sessionType'];
                        $clientDetails = $userSessionObj['clientDetails'];
                        $lastSyncedAt = $userSessionObj['lastSyncedAt'];
                        $isCurrSession = $userSessionObj['isCurrSession'];
                        $deviceModelName = $userSessionObj['deviceModelName'];
                        $formattedLastSyncedAt = dbDateTimeToDispDateTimeWithTZ($lastSyncedAt, $tzStr);
                        @endphp
                        <div class="row sessionRowDiv">
                            <div class="col-md-4">
                                {{ $sessionType }} 
                                @if(isset($deviceModelName) && $deviceModelName != "")
                                    <br/>
                                    {{ $deviceModelName }} 
                                @endif
                            </div> 
                            <div class="col-md-5">
                                {{ $formattedLastSyncedAt }}
                            </div>
                            <div class="col-md-3" align="right">
                                @if($isCurrSession == 0)
                                    <button type="button" class="btn btn-primary btn-success btn-xs" onclick="performRemoveAppuserSession('{{ $sessionId }}')">
                                        Remove
                                    </button>
                                @endif
                            </div>
                        </div>  
                        <div class="row">
                            <div class="col-md-12">  
                                {{ $clientDetails }}
                            </div> 
                        </div>  
                    @endforeach
                @endif
            </div>

            <!-- <div class="modal-footer">

            </div> -->
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function(){

    });

    function performRemoveAppuserSession(sessionId)
    {          
        bootbox.dialog({
            message: "Do you really want to remove this session?",
            title: "Confirm Remove Session",
                buttons: {
                    yes: {
                    label: "Yes",
                    className: "btn-primary",
                    callback: function() {
                        $.ajax({
                            type: 'POST',
                            url: "{!!  route('appuser.removeExistingAppuserSession') !!}",
                            dataType: "json",
                            data:"userId="+"{{ $userId }}"+"&loginToken="+"{{ $loginToken }}"+"&usrSessionId="+sessionId+"&userIsLoggedIn="+"{{ $userIsLoggedIn }}",
                        })
                        .done(function(data){
                            if(data.status*1 == 1)
                            {   
                                if(data.msg != "")
                                    successToast.push(data.msg);

                                @if($userIsLoggedIn == 0)
                                performUserAuthentication();
                                @endif
                            }
                            else
                            {
                                if(data.msg != "")
                                    errorToast.push(data.msg);
                            }

                            $('#appuserSessionManagementModal').modal('hide');

                            @if($userIsLoggedIn == 1)
                            loadAppuserSessionManagementView();
                            @endif
                        })
                        .fail(function(xhr,ajaxOptions, thrownError) {
                        })
                        .always(function() {
                        });
                    }
                },
                no: {
                    label: "No",
                    className: "btn-primary",
                    callback: function() {
                    }
                }
            }
        });
    }
</script>