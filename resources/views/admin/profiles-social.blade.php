@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('admin.profiles_social') }}
          </h4>
        </section>

        <!-- Main content -->
        <section class="content">

        	 @if(Session::has('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		       <i class="fa fa-check margin-separator"></i> {{ Session::get('success_message') }}
		    </div>
		@endif

        	<div class="content">

        		<div class="row">

        	<div class="box">
                <div class="box-header">
                  <h3 class="box-title">{{ trans('admin.profiles_social') }}</h3>
                </div><!-- /.box-header -->

                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url('panel/admin/profiles-social') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Facebook</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->facebook }}" name="facebook" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Twitter</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->twitter }}" name="twitter" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Pinterest</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->pinterest }}" name="pinterest" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Instagram</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->instagram }}" name="instagram" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Youtube</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->youtube }}" name="youtube" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Github</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->github }}" name="github" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Tiktok</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->tiktok }}" name="tiktok" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Snapchat</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $settings->snapchat }}" name="snapchat" class="form-control" placeholder="{{ trans('admin.url_social') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <div class="box-footer">
                    <button type="submit" class="btn btn-success">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>

        		</div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
