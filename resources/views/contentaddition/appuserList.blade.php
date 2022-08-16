@extends('admin_template')

@section('int_scripts')
<script>
	var appuserListTable;
	var frmObj = $('#frmAppuserFilters');
	$(document).ready(function(){
		var moduleName = '{{ $page_title }}';
		var visibleColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
	    appuserListTable = $('#users-table').DataTable({
	        processing: true,
	        serverSide: true,
        	lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
		    dom: 'lrtip',
	        ajax: {
	            url: "{!!  route('appuserContentDatatable') !!}",
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
	            { data: '7', name: 'last_synced_at', searchable: false },
	            { data: '8', name: 'ref_code', searchable: false }
	        ],
	        "order": [[ 4, "desc" ]]
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
        
        
		$("#txtRefCode").on('input', function (){
			frmObj.submit();
		});
		$("#selRegType").on('change', function (){
			frmObj.submit();
		});
		$("#selVerStatus").on('change', function (){
			frmObj.submit();
		});
		$("#selStatus").on('change', function (){
			frmObj.submit();
		});
		$("#selGender").on('change', function (){
			frmObj.submit();
		});
		$('#txtRegFromDate').on('changeDate', function(e) {
			frmObj.submit();
		});
		$('#txtRegToDate').on('changeDate', function(e) {
			frmObj.submit();
		});
		$('#txtSyncFromDate').on('changeDate', function(e) {
			frmObj.submit();
		});
		$('#txtSyncToDate').on('changeDate', function(e) {
			frmObj.submit();
		});
	});
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
            	@include('contentaddition.partialview._advancedSearch')
			    <div class="table">
			        <table id="users-table" class="table table-bordered">
			            <thead>
			                <tr>
			                    <th>Name</th>
			                    <th>Email</th>
			                    <th>Contact</th>
			                    <th>Country</th>
			                    <th>Registered On</th>
			                    <th>Allotted MB</th>
			                    <th>Available MB</th>
			                    <th>Last Synced On</th>
			                    <th>Refferal Code</th>
			                </tr>
			            </thead>
			        </table>
			    </div>
				{{ Form::hidden('cont_add_id', $addContentId, ['id' => 'send_id']) }}
			</div>
		</div>
	</div>
</div>
@endsection