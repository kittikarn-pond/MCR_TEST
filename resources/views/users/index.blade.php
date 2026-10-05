@extends('layouts.app')

@section('title', 'จัดการผู้ใช้งาน')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">จัดการผู้ใช้งาน</h1>
    <a href="{{ route('users.create') }}" class="btn btn-primary">+ เพิ่มผู้ใช้งาน</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>ชื่อ</th>
                    <th>อีเมล</th>
                    <th>วันที่สร้าง</th>
                    <th class="text-end">จัดการ</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">แก้ไข</a>
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                              onsubmit="return confirm({{ Illuminate\Support\Js::from('ยืนยันการลบผู้ใช้งาน '.$user->name.' ?') }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีผู้ใช้งาน</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>
@endsection