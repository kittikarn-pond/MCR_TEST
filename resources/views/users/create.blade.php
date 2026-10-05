@extends('layouts.app')

@section('title', 'เพิ่มผู้ใช้งาน')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4">เพิ่มผู้ใช้งาน</h1>
        @include('users._form', ['action' => route('users.store'), 'method' => 'POST', 'cancel' => route('users.index')])
    </div>
</div>
@endsection