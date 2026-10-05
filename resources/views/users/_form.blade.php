{{--
    Shared by users.create, users.edit and profile.edit.
    Expects: $action, $method ('POST' | 'PUT'), optional $user, optional $cancel (URL).
--}}
@php($editing = isset($user))

<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">ชื่อ</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">อีเมล</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input type="password" id="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password" @required(! $editing)>
        @if ($editing)<div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>@endif
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">ยืนยันรหัสผ่าน</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               class="form-control" autocomplete="new-password" @required(! $editing)>
    </div>

    <button type="submit" class="btn btn-primary">บันทึก</button>
    @isset($cancel)
        <a href="{{ $cancel }}" class="btn btn-outline-secondary">ยกเลิก</a>
    @endisset
</form>