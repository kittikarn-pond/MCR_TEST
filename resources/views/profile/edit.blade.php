@extends('layouts.app')

@section('title', 'แก้ไขโปรไฟล์')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4">แก้ไขโปรไฟล์</h1>
        @include('users._form', ['action' => route('profile.update'), 'method' => 'PUT', 'user' => $user, 'cancel' => route('dashboard')])
    </div>
</div>
@endsection