
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.User.role.dataTable')
@endsection

@section('model')
  @include('pages.User.role.create')
  @include('pages.User.role.update')
@endsection
