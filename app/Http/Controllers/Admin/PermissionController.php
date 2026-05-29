<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Http\Request;

class PermissionController extends AdminController
{
    public function getIndex()
    {
        $users = User::where('role_id', 4)->get();
        return view('admin.permissions.index', compact('users'));
    }

    public function getList(Request $request)
    {
        $list = User::where('role_id', 4)
            ->select('id', 'name', 'username', 'email');

        return \DataTables::of($list)->make(true);
    }
    

    public function getUpdate($id)
    {
        $user = User::findOrFail($id);
        return view('admin.permissions.update', compact('user'));
    }

    public function postUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->permissions = json_encode($request->permissions);
        $user->save();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Permissions updated successfully!');
    }
}