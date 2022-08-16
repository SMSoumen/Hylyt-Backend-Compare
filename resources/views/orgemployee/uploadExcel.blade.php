@extends('ws_template')

@section('int_scripts')
<script>
	$(document).ready(function(){
		$('#frmImportData').formValidation({
			framework: 'bootstrap',
			icon:
			{
				valid: "{!!  Config::get('app_config.validation_success_icon') !!}",
				invalid: "{!!  Config::get('app_config.validation_failure_icon') !!}",
				validating: "{!!  Config::get('app_config.validation_ongoing_icon') !!}"
			},
			fields:
			{
				//General Details
				
			}
		})
		.on('success.form.fv', function(e) {
            // Prevent form submission
            e.preventDefault();

            // Some instances you can use are
            var $form = $(e.target),        // The form instance
                fv    = $(e.target).data('formValidation'); // FormValidation instance

            // Do whatever you want here ...
            importEmployeeData($form);
        });
	});
	
	function downloadEmployeeTemplate(uri) 
	{
		bootbox.dialog({
			message: 'Template will be downloaded shortly',
			title: "",
				buttons: {
				no: {
					label: "OK",
					className: "btn-primary",
					callback: function() {
					}
				}
			}
		});	

	    var link = document.createElement("a");
	    link.href = uri;
	    link.click();
	}
	
	function importEmployeeData(frmObj)
	{
		bootbox.dialog({
			message: 'Hold on! Sheet is getting uploaded!!',
			title: "",
				buttons: {
				no: {
					label: "OK",
					className: "btn-primary",
					callback: function() {
					}
				}
			}
		});	

		var dataToBeSent = new FormData($(frmObj)[0]);
		
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: "{!!  route('orgEmployee.import') !!}",
			dataType: "json",
        	contentType: false,
        	processData: false,
			data: dataToBeSent,
		})
		.done(function(data){
			if(data.status*1 == 1)
			{	
				$('#divEmployeeImport').html(data.view);
			}
		})
		.fail(function(xhr,ajaxOptions, thrownError) {
		})
		.always(function() {
		});	
	}
	</script>
	@endsection
@section('content')
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	Import Employee(s)
		        	&nbsp;&nbsp;
		        	<a href="javascript:void(0);" class="btn btn-success btn-sm" onclick="downloadEmployeeTemplate('{{ $empTemplateUri }}');">
			    		<i class="fa fa-download"></i>&nbsp;&nbsp;
			    		Download Template
			    	</a>
		        </h3>
			</div>
           <div class="box-body">
           		{!! Form::open(['url' => '', 'class' => 'form-vertical', 'id' => 'frmImportData', 'enctype' => "multipart/form-data"]) !!}
           			<div class="row">
           				<div class="col-md-9">
           					<div class="form-group">
								{!! Form::label('import_file', 'Import File', ['class' => 'control-label text-align']) !!}
								{!! Form::file('import_file', ['class' => 'form-control', 'autocomplete' => 'off', 'id' => 'import_file']) !!}
							</div>
           				</div>
           			</div>
					{!! Form::hidden('usrtoken', $usrtoken) !!}
           			<div class="row">
						<div class="col-md-12" align="right">
							{!! Form::button('<i class="fa fa-upload"></i>&nbsp;&nbsp;Import', ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
						</div>
					</div>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>

<div id="divEmployeeImport"></div>
@endsection