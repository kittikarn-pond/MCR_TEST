@extends('layouts.app')

@section('title', 'หน้าหลัก')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h4">หน้าหลัก (Dashboard)</h1>
        <p class="mb-3">ยินดีต้อนรับ, {{ auth()->user()->name }}</p>
        <a href="{{ route('users.index') }}" class="btn btn-primary">ไปที่หน้าจัดการผู้ใช้งาน</a>
    </div>
</div>
@endsection