@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-4 text-center">เข้าสู่ระบบ</h1>

                @error('email')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                        <input type="password" id="password" name="password"
                               class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">จดจำการเข้าสู่ระบบ</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection