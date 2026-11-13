@extends('layouts.admin')

@section('title', 'New Requisition Form')

@section('content')
    <livewire:admin-reservation-form :adminId="auth('sanctum')->id() ?? auth()->id()" />
@endsection
