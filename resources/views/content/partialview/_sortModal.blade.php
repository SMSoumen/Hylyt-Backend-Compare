@php
	$sortByContent = Config::get('app_config.sort_by_content');
	$sortByType = Config::get('app_config.sort_by_type');
	$sortByCreateDate = Config::get('app_config.sort_by_create_date');
	$sortByUpdateDate = Config::get('app_config.sort_by_update_date');
	$sortByDueDate = Config::get('app_config.sort_by_due_date');
	$sortByFolder = Config::get('app_config.sort_by_folder');
	$sortByTag = Config::get('app_config.sort_by_tag');
	$sortBySize = Config::get('app_config.sort_by_size');
@endphp
<div id="sortContentModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			{!! Form::open(['url' => '', 'class' => 'form-horizontal', 'id' => 'frmSortContent']) !!}
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						&times;
					</button>
					<h4 class="modal-title">
						{{ $page_description or null }}		
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-offset-1 col-md-10">
							<div class="form-group">
								{{ Form::radio('sortBy', $sortByContent, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByContent]) }}
								{!! Form::label('sortBy'.$sortByContent, '&nbsp;&nbsp;Sort By Content', ['class' => 'control-label']) !!}
							</div>

							@if($appHasTypeReminder == 1 || $appHasTypeCalendar == 1)
								<div class="form-group">
									{{ Form::radio('sortBy', $sortByType, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByType]) }}
									{!! Form::label('sortBy'.$sortByType, '&nbsp;&nbsp;Sort By Type (Reminder, Archive, Calendar)', ['class' => 'control-label']) !!}
								</div>
							@endif
							
							<div class="form-group">
								{{ Form::radio('sortBy', $sortByCreateDate, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByCreateDate]) }}
								{!! Form::label('sortBy'.$sortByCreateDate, '&nbsp;&nbsp;Sort By Creation Date', ['class' => 'control-label']) !!}
							</div>
							
							<div class="form-group">
								{{ Form::radio('sortBy', $sortByUpdateDate, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByUpdateDate]) }}
								{!! Form::label('sortBy'.$sortByUpdateDate, '&nbsp;&nbsp;Sort By Modification Date', ['class' => 'control-label']) !!}
							</div>
							
							@if($appHasTypeReminder == 1 || $appHasTypeCalendar == 1)
								<div class="form-group">
									{{ Form::radio('sortBy', $sortByDueDate, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByDueDate]) }}
									{!! Form::label('sortBy'.$sortByDueDate, '&nbsp;&nbsp;Sort By Due Date', ['class' => 'control-label']) !!}
								</div>
							@endif
							
							@if($isFolder)
								<div class="form-group">
									{{ Form::radio('sortBy', $sortByFolder, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByFolder]) }}
									{!! Form::label('sortBy'.$sortByFolder, '&nbsp;&nbsp;Sort By Folder', ['class' => 'control-label']) !!}
								</div>
							@endif
							
							<div class="form-group">
								{{ Form::radio('sortBy', $sortByTag, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortByTag]) }}
								{!! Form::label('sortBy'.$sortByTag, '&nbsp;&nbsp;Sort By Tag', ['class' => 'control-label']) !!}
							</div>
							
							<div class="form-group">
								{{ Form::radio('sortBy', $sortBySize, FALSE, ['class' => 'sortBy', 'id' => 'sortBy'.$sortBySize]) }}
								{!! Form::label('sortBy'.$sortBySize, '&nbsp;&nbsp;Sort By Content Size', ['class' => 'control-label']) !!}
							</div>
						</div>
					</div>						
				</div>
				<div class="modal-footer">
					<div class="col-md-12">
						<div class="col-md-6" align="left">
							{!! Form::button('<i class="fa fa-sort-amount-desc"></i>&nbsp;&nbsp;Reverse', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'sortContentList(-1, '.$isFolderFlag.', '.$isFavoritesTab.');', 'id' => 'btnReverseSort']) !!}
						</div>
						<div class="col-md-6" align="right">
							{!! Form::button('<i class="fa fa-sort-amount-asc"></i>&nbsp;&nbsp;Sort', ['type' => 'button', 'class' => 'btn btn-primary', 'onclick' => 'sortContentList(1, '.$isFolderFlag.', '.$isFavoritesTab.');', 'id' => 'btnSort']) !!}
						</div>
						
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>

<script>
	$(document).ready(function(){
  		$('.sortBy').iCheck({
    		radioClass: 'iradio_flat-blue',
  		});
  		
  		@if($sortBy > 0)
  			sortVal = $("#sortBy"+"{{ $sortBy }}").val();
  			$("#sortBy"+"{{ $sortBy }}").iCheck('check');
  		@endif
	});
</script>