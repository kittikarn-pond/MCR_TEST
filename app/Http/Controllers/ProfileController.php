<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UserRequest $request): RedirectResponse
    {
        $request->user()->update($request->userData());

        return redirect()->route('profile.edit')->with('status', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
    }
}