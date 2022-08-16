<style>
	.divOptionList
	{
		max-height: 400px;
    	overflow: auto;
	}
</style>
<div id="contentDependencyModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmContentDependency', 'onsubmit' => 'return false;']) !!}
				<div class="modal-header">
					<!--<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>-->
					<h4 class="modal-title">
						{{ $modalTitle or null }}		
					</h4>
				</div>
				<div class="modal-body">
					@if($showSearchBar)
						<div class="row" id="divSearchParticipant">
							<div class="row">
								<div class="col-md-offset-1 col-md-10">
									{!! Form::text('searchStr', NULL, ['class' => 'form-control text-cap', 'autocomplete' => 'off', 'placeholder' => 'Search', 'id' => 'searchStr']) !!}
									<br/>
								</div>
							</div>
						</div>
					@endif
					<div class="row">
						<div class="col-md-offset-1 col-md-10 divOptionList" id="divOptionList">
							@foreach($depDataArr as $depData)
								@php
								$id = $depData['id'];
								$text = $depData['text'];
								$selVisibility = "visibility:hidden";
								if(in_array($id, $selectedIdArr)) {
									$selVisibility = "visibility:visible";
								}
								$highlightedRowClass = '';
								if($id == "") {
									$highlightedRowClass = "highlightedRowClass";
								}
								@endphp
								<div class="row depSelectRow {{ $highlightedRowClass }}" onclick='setSelection(this, "{{ $id }}", "{{ $text }}")'>
									<input type="hidden" class="txtDepId" value="{{ $id }}" />
									<div class="col-md-10">	
										{{ $text }}				
									</div>
									<div class="col-md-1" align="right">
										<span class="depSelected" style="{{ $selVisibility }}">
											<i class="fa fa-check"></i>
										</span>
									</div>
								</div>	
							@endforeach	
						</div>
					</div>		
				</div>
				<div class="modal-footer">
					<div class="col-md-12" align="right">
						@if($hasDone == 1)
							{!! Form::button('<i class="fa fa-save"></i>&nbsp;&nbsp;Done', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'doneClicked();', 'id' => 'btnDone']) !!}
						@endif	
						@if($hasCancel == 1)
							{!! Form::button('<i class="fa fa-times"></i>&nbsp;&nbsp;Cancel', ['type' => 'button', 'class' => 'btn btn-danger', 'onclick' => 'cancelClicked();', 'id' => 'btnCancel']) !!}
						@endif					
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
<style>
	.depSelectRow
	{
		border-bottom: 1px solid black;
		padding: 10px;
		cursor: pointer;
	}
	.highlightedRowClass
	{
		color: red;
	}
</style>
<script>
	var primaryDependencyArr = [];
	var filteredDependencyArr = [];

	var primarySelectedArr = [], primarySelectedTextArr = [];
	var fieldId = "{{ $fieldId }}";
	var displayFieldId = "{{ $displayFieldId }}";
	var isMandatory = {{ $isMandatory }};
	var isMultiSelect = {{ $isMultiSelect }};
	var isIntVal = {{ $isIntVal }};
	var hasDone = {{ $hasDone }};
	
	@foreach($selectedIdArr as $selectedId)
		var selectedId = "{{ $selectedId }}";
		if(isIntVal == 1)
			selectedId = selectedId*1;
		primarySelectedArr.push(selectedId);
		
		var selectedText = '';
		@if(isset($selectedTextArr) && isset($selectedTextArr[$selectedId]))
			var selectedText = "{{ $selectedTextArr[$selectedId] }}";
		@endif
		
		primarySelectedTextArr.push(selectedText);
	@endforeach
	
	var currSelectedArr = primarySelectedArr;
	var currSelectedTextArr = primarySelectedTextArr;
	
	$(document).ready(function(){

       	primaryDependencyArr = <?php echo json_encode($depDataArr, JSON_PRETTY_PRINT) ?>;
       	filteredDependencyArr = primaryDependencyArr;

       	$("#searchStr").keyup(function(event){
			var searchStr = $("#searchStr").val();
			searchStr = searchStr.trim().toLowerCase();
			filterDependencyList(searchStr);
		});
  		
	});
	
	function setSelection(obj, depId, depText)
	{		
		// console.log('setSelection : depId : ', depId, ' : depText : ', depText)
		if(isIntVal == 1)
		{
			depId = depId*1;
		}
			
		if($.inArray(depId, currSelectedArr) >= 0) 
		{
			currSelectedArr = jQuery.grep(currSelectedArr, function(value) {
			  return value != depId;
			});
			
			currSelectedTextArr = jQuery.grep(currSelectedTextArr, function(value) {
			  return value != depText;
			});
		}
		else 
		{
			if(isMultiSelect == 0) {
				currSelectedArr = [];
				currSelectedTextArr = [];
			}
			currSelectedArr.push(depId);
			currSelectedTextArr.push(depText);
		}
		
		setListSelection();
		
		if(hasDone == 0)
		{
			sendDataToBase();
		}
	}
	
	function setListSelection()
	{		
		$( "#divOptionList .depSelectRow" ).each(function( index ) {
			var depId = $(this).find(".txtDepId").val();
			
			if(isIntVal == 1)
			{
				depId = depId*1;
			}
				
			if($.inArray(depId, currSelectedArr) >= 0) {
		  		$(this).find(".depSelected").css("visibility", "visible");				
			}
			else {
		  		$(this).find(".depSelected").css("visibility", "hidden");				
			}
		});
	}
	
	function doneClicked() 
	{
		sendDataToBase();
	}
	
	function cancelClicked() 
	{
		currSelectedArr = primarySelectedArr;
		currSelectedTextArr = primarySelectedTextArr;
		sendDataToBase();
	}
	
	function sendDataToBase()
	{
		if(isMandatory == 0 || (isMandatory == 1 && currSelectedArr.length > 0))
		{
			var valToBeSet, valToBeDisplayed;
			if(isMultiSelect == 1) 
			{
				valToBeSet = JSON.stringify(currSelectedArr);
				valToBeDisplayed = JSON.stringify(currSelectedTextArr);
			}
			else
			{
				valToBeSet = currSelectedArr[0];
				valToBeDisplayed = currSelectedTextArr[0];
			}
			
			$('input[name="' + fieldId + '"]').val(valToBeSet);
			@if($hasDisplayField)
				$('#' + displayFieldId).text(valToBeDisplayed);
			@endif
			$("#contentDependencyModal").modal("hide");
			@if($hasCallback)
				window['{{ $callbackName }}'](valToBeSet);
			@endif
		}		
		else
		{
			errorToast.push("Please select a value first");
		}	
	}

	function filterDependencyList(searchStr)
	{
		filteredDependencyArr = primaryDependencyArr.filter(function (e) {
						return (((e.text).toLowerCase().indexOf(searchStr) >= 0));
					});
		reloadDependencyListView();
	}

	function reloadDependencyListView()
	{
		$('#divOptionList').html('');
       	for(i = 0; i < filteredDependencyArr.length; i++)
       	{
       		const indDepObj = filteredDependencyArr[i];

        	var indDepId = indDepObj.id;
        	var indDepName = indDepObj.text;
        	var indSelVisibility = 'visibility:hidden';
			if(primarySelectedArr.indexOf(indDepId) >= 0) {
				indSelVisibility = "visibility:visible";
			}
			var indHighlightedRowClass = '';
			if(indDepId < 0) {
				indHighlightedRowClass = "highlightedRowClass";
			}


        	var depObjRowHtml = '';
        	depObjRowHtml += '<div class="row depSelectRow ' + indHighlightedRowClass + '" onclick="setSelection(\'\', \'' + indDepId + '\', \'' + indDepName + '\');">';
			depObjRowHtml += '<input type="hidden" class="txtDepId" value="' + indDepId + '" />';
			depObjRowHtml += '<div class="col-md-10">';
			depObjRowHtml += indDepName;
			depObjRowHtml += '</div>';
			depObjRowHtml += '<div class="col-md-1" align="right">';
			depObjRowHtml += '<span class="depSelected" style="' + indSelVisibility + '">';
			depObjRowHtml += '<i class="fa fa-check"></i>';
			depObjRowHtml += '</span>';
			depObjRowHtml += '</div>';
			depObjRowHtml += '</div>';

			$('#divOptionList').append(depObjRowHtml);
       	}
	}
</script>