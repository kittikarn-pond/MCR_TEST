@extends('layouts.app')

@section('title', 'แก้ไขผู้ใช้งาน')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4">แก้ไขผู้ใช้งาน</h1>
        @include('users._form', ['action' => route('users.update', $user), 'method' => 'PUT', 'user' => $user, 'cancel' => route('users.index')])
    </div>
</div>
@endsection