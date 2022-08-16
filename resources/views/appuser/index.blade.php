@extends('admin_template')

@section('int_scripts')
<script>
	var appuserListTable;
	var frmObj = $('#frmAppuserFilters');
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
	    appuserListTable = $('#users-table').DataTable({
	    	scrollX: true,
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	         ajax: {
	            url: "{!!  route('appuserServerDatatable') !!}",
	            method: 'GET',
	            "data": function ( data ) {
	            	data = getAppuserDataForTable(data);
				}
	        },
	        columns: [
	            { data: '0', name: 'fullname' },
	            { data: '1', name: 'email' },
	            { data: '2', name: 'contact' },
	            { data: '3', name: 'country' },
	            { data: '4', name: 'reg_date', searchable: false },
	            { data: '5', name: 'allotted_mb', searchable: false },
	            { data: '6', name: 'available_mb', searchable: false },
	           // { data: '7', name: 'note_count', sortable: false, searchable: false},
	            { data: '7', name: 'ref_code', searchable: false },
	            { data: '8', name: 'premium_str', searchable: false },
	            { data: '9', name: 'enterp_str', searchable: false },
	            { data: '10', name: 'last_synced_at', searchable: false },
	            { data: '11', name: 'status', sortable: false, searchable: false },
	            { data: '12', name: 'action', sortable: false, searchable: false }
	        ],
	        "order": [[ 4, "desc" ]],
	        dom: 'lBfrtip',
	        buttons: [
		        {
	                extend: 'excelHtml5',
	                title: 'Appuser_List_'+getCurrentDateTimeStr(),
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
	            {
	                extend: 'pdfHtml5',
	                title: 'Appuser_List_'+getCurrentDateTimeStr(),
	                exportOptions: {
	                    columns: visibleColumns
	                }
	            },
	            {
	                extend: 'csvHtml5'
	            },
	        ],
		    columnDefs: [
	        	{
	                "targets":  11,
	                "render": function ( data, type, row )
	                {
	                	var dataArr = data.split("_");
	                	var id = dataArr[0];
	                	var isActive = dataArr[1];
                		var fnName = "changeStatus";

				        var btnClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_class') }}" :  "{{ Config::get('app_config.inactive_btn_class') }}";
						var iconClass = (isActive==1) ? "{{ Config::get('app_config.active_btn_icon_class') }}" :  "{{ Config::get('app_config.inactive_btn_icon_class') }}";
						var event = 'changeStatus("'+moduleName+'","'+id+'",'+isActive+','+fnName+');';						
						var statusText =(isActive==1) ? "{{ Config::get('app_config.active_btn_text') }}" :  "{{ Config::get('app_config.inactive_btn_text') }}" ;

	                    return "<button class='btn btn-xs "+btnClass+"' onclick ='"+event+"'>"
	                    		+"<i class='fa "+iconClass+"'></i>&nbsp;"+statusText+"</button>";
	                }
	            }           
       		]
	    });
		$("#resetFilters").trigger('click');

	    $(frmObj).formValidation({
            framework: 'bootstrap',
		    icon: {
		        valid: 'glyphicon glyphicon-ok',
		        invalid: 'glyphicon glyphicon-remove',
		        validating: 'glyphicon glyphicon-refresh'
		    },
            fields: {                               	               	
             	txtRegFromDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    },
                        callback: {
                            message: 'The value must be less than or equal to Range End',
                            callback: function (value, validator, $field) {
                            	var max = $('#txtRegToDate').val();  

                            	if(max != "")
                            	{
                            		var maxDate = new Date(max.split("-").reverse().join("-"));
                            		var valDate = new Date(value.split("-").reverse().join("-"));
                            		return valDate <= maxDate;
                            	}
                            	return true;
                            }
                        }
                    }
                },           	               	
             	txtRegToDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    }
                    }
                },                            	               	
             	txtSyncFromDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    },
                        callback: {
                            message: 'The value must be less than or equal to Range End',
                            callback: function (value, validator, $field) {
                            	var max = $('#txtSyncToDate').val();  

                            	if(max != "")
                            	{
                            		var maxDate = new Date(max.split("-").reverse().join("-"));
                            		var valDate = new Date(value.split("-").reverse().join("-"));
                            		return valDate <= maxDate;
                            	}
                            	return true;
                            }
                        }
                    }
                },           	               	
             	txtSyncToDate: {
                    validators: {
                    	date: {
	                        format: 'DD-MM-YYYY',
	                        message: 'The value is not a valid date'
	                    }
                    }
                }
            }
        })
        .on('success.form.fv', function(e) {
        	// Prevent form submission
            e.preventDefault();
			$(frmObj).data('formValidation').resetForm();

			appuserListTable.ajax.reload();
        });
        
        function getCurrentDateTimeStr()
        {
			var dtStr = new Date().getDate()+'-'+((new Date().getMonth()*1)+1)+'-'+new Date().getFullYear()+'_'+new Date().getHours()+'-'+new Date().getMinutes();
			return dtStr;
		}
	});
	
	function redrawAppuserTable()
	{
		appuserListTable.ajax.reload();
	}
</script>
@stop

@section('content')
<div class="row">
	<div class="col-md-12">
		@if(Session::has('flash_message'))
			<div class="row">
				<div class="col-md-12">
				    <div class="alert alert-info alert-dismissible">
                		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						<i class="icon fa fa-info-circle"></i>&nbsp;&nbsp;
				        <strong> {{ Session::get('flash_message') }} </strong> 
				    </div>
				</div>
			</div>
		@endif
		
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	{{ $page_description or null }}
		        </h3>
		    </div>
            <div class="box-body">
            	@include('appuser.partialview._advancedSearch')
			    <div class="table">
			        <table id="users-table" class="table table-bordered" width="100%">
			            <thead>
			                <tr>
			                    <th>Name</th>
			                    <th>Email</th>
			                    <th>Contact</th>
			                    <th>Country</th>
			                    <th>Registered On</th>
			                    <th>Allotted MB</th>
			                    <th>Available MB</th>
			                	<!--<th>Notes Count</th>-->
			                    <th>Refferal Code</th>
			                    <th>Account Type</th>
			                    <th>Enterprise Account</th>
			                    <th>Last Synced On</th>
			                    <th>Status</th>
			                    <th>Action</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::open(array('url' => route('appuserServer.show'), 'id' => 'frmViewAppuser')) }}
					{!! Form::hidden('appuser_id', 0, ['id' => 'viewId']) !!}
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
<div id="divSendNotif"></div>
<div id="divAddContent"></div>
@endsection