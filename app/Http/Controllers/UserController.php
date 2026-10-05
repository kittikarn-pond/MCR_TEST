<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderBy('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->userData());

        return redirect()->route('users.index')->with('status', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        // When editing yourself, update the authenticated instance so the session's password
        // hash is refreshed and this session survives a password change (others are logged out).
        $user = $user->is($request->user()) ? $request->user() : $user;

        $user->update($request->userData());

        return redirect()->route('users.index')->with('status', 'แก้ไขผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('users.index')->with('error', 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }
}