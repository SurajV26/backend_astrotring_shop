<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class ProfileController extends AdminController
{
    public function getIndex(Request $request)
    {
        $user = auth()->user();
        $user->role = \App\Models\Role::whereId($user->role_id)->value('name');

        return view('admin.profile.index', compact('user'));
    }

    public function postUpdate(Request $request)
    {
        // dd($request->all());
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'mobile_no' => 'required|digits:10',
            'address' => 'nullable|max:1000',
            'company_name' => 'nullable|string|max:255',
            'affiliate_type' => 'nullable|in:blogger,influencer,agency,publisher,other',
            'traffic_sources' => 'nullable|array',
            'promotion_plan' => 'nullable|string',
            'expected_leads' => 'nullable|in:less_than_50,50_100,100_500,500_plus',
            'profile_image' => 'nullable|mimes:jpeg,png,bmp,jpg|max:4096',
            'is_two_factor_auth_enabled' => 'nullable|in:0,1',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $dataObj = objFromPost(['name', 'email', 'mobile_no', 'address', 'company_name', 'affiliate_type', 'traffic_sources', 'promotion_plan', 'expected_leads', 'is_two_factor_auth_enabled']);

        try {
            $user = auth()->user();
            $user->name = $dataObj->name;
            $user->mobile = $dataObj->mobile_no;
            $user->address = $dataObj->address;

            if ($user->type == 'employee') {
                $user->company_name = $dataObj->company_name;
                $user->affiliate_type = $dataObj->affiliate_type;
                $user->traffic_sources = $dataObj->traffic_sources;
                $user->promotion_plan = $dataObj->promotion_plan;
                $user->expected_leads = $dataObj->expected_leads;
            }

            if ($dataObj->email) {
                $user->email = strtolower($dataObj->email);
            }

            if (!$user->isStaff()) {
                $user->is_two_factor_auth_enabled = $dataObj->is_two_factor_auth_enabled ?? 0;
            }

            // if ($user->profile_image && $request->is_file_removed == 1) {
            //     unlink(public_path("uploads/images/{$user->profile_image}"));
            //     $user->profile_image = null;
            // }

            // if ($request->profile_image) {
            //     $user->profile_image = uploadFile('profile_image');
            // }

            if ($request->hasFile('profile_image')) {
                $user['profile_image'] = uploadFile('profile_image', '128', '128', 'user', $user->profile_image);
            }

            $user->save();

            return response()->json(['message' => 'Your request processed successfully.']);
        } catch (\Throwable $th) {
            \Log::error($th);
            return response()->json(['message' => 'Failed to process your request. Please try again later.'], 422);
        }
    }

    public function postChangePassword(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $dataObj = objFromPost(['current_password', 'new_password']);

        try {
            $user = auth()->user();

            if (!\Hash::check($dataObj->current_password, $user->password)) {
                return response()->json(['message' => 'Please enter valid current password.'], 422);
            } elseif (\Hash::check($dataObj->new_password, $user->password)) {
                return response()->json(['message' => 'Current password and new password can not be same.'], 422);
            }

            $user->password = bcrypt($dataObj->new_password);
            $user->save();

            return response()->json(['message' => 'Your request processed successfully.']);
        } catch (\Throwable $th) {
            \Log::error($th);
            return response()->json(['message' => 'Failed to process your request. Please try again later.'], 422);
        }
    }

    public function getLogout(Request $request)
    {
        if (auth()->user()->isStaff()) {
            $login_request_status = $request->session()->get('login_request_status');
            $login_request_hash = $request->session()->get('login_request_hash');

            if ($login_request_status == 'pending' && $login_request_hash) {
                \App\Models\LoginRequest::where('hash_token', $login_request_hash)->update(['status' => 'logged_out']);
            }
        }

        auth()->logout();
        return redirect()->route('auth.login.index');
    }
}
