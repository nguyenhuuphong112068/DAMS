@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.StorageLocation.Tier.dataTable')
@endsection

@section('model')
    @include('pages.StorageLocation.Tier.create')
    @include('pages.StorageLocation.Tier.update')
@endsection
