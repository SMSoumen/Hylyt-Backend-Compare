@php
$dtClass = "";
if($fieldTypeName == $fieldTypeDate)
{
	$dtClass = "sandbox-container";
	$fieldValue = dbToDispDate($fieldValue);
}
@endphp
<div class="col-md-6 {{ $dtClass }}">
	<div class="form-group">
		{!! Form::label($fieldInpName, $fieldDispName, ['class' => 'control-label']) !!}
		@if($isView)
			<br/>
			{{ $fieldValue }}
		@else
			@if($fieldTypeName == $fieldTypeText || $fieldTypeName == $fieldTypeDate)
				{!! Form::text($fieldInpName, $fieldValue, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => $fieldInpName]) !!}
			@elseif($fieldTypeName == $fieldTypeNumber)
				{!! Form::number($fieldInpName, $fieldValue, ['class' => 'form-control', 'autocomplete' => 'off', 'id' => $fieldInpName]) !!}
			@endif
		@endif
	</div>
</div>