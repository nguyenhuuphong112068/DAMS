
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.User.permission.dataTable')
@endsection

@section('model')
  @include('pages.User.permission.create')
  @include('pages.User.permission.update')
@endsection

