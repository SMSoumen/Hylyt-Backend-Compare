@extends('admin_template')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="row">
	<div class="col-md-12">
		<!-- Box -->
		<div class="box box-primary">
		    <div class="box-header with-border">
		        <h3 class="box-title">
		        	Upload Excel
		        </h3>
		    </div>
            <div class="box-body">
			    <input type="file" name="import_file" />
					{{ csrf_field() }}
					<br/>
				<button class="btn btn-primary" type="submit">Import Excel File</button>

			</div>
		</div>
	    @if ($errors->any())
	        <ul class="alert alert-danger">
	            @foreach ($errors->all() as $error)
	                <li>{{ $error }}</li>
	            @endforeach
	        </ul>
	    @endif
	</div>
</div>
@endsection