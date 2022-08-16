<div class="row attAddRow">						
	<div class="col-md-offset-1 col-md-8">
		<div class="detailsRow">
			{!! Form::file('attachment_file[]', ['class' => 'form-control input-xs attachment_file', 'id' => 'test', 'autocomplete' => 'off', 'placeholder' => 'Select Document']) !!}
		</div>
	</div>
	<div class="col-md-2" align="right">
		<!--<button type="button" class="btn btn-sm btn-purple" onclick="viewInputFile(this);"><i class="fa fa-arrows-alt"></i></button>-->
		<button type='button' class='btn btn-sm btn-danger' onclick='removeInputFile(this);'><i class='fa fa-trash'></i></button>
	</div>
</div>