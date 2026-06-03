@extends('layouts.master')

@section('title')
    Profile
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Profile</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card border">
                <form id="profileFrm">
                    @csrf
                    <input type="hidden" name="is_file_removed" value="0">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Profile Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <input type="file" id="input-file-now" name="profile_image" class="dropify"
                                        data-default-file="{{ $admin->profile_image ? asset('storage/user/' . $admin->profile_image) : '' }}" />
                                    <small class="text-muted"><b>Example::</b> image size - 128x128.</small>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-label">Username :</label>
                                            <input type="text" class="form-control" value="{{ $admin->username }}"
                                                disabled />
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-label">Role :</label>
                                            <input type="text" class="form-control" value="{{ $admin->role }}"
                                                disabled />
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Name <sup
                                                    class="text-danger fs-5">*</sup>
                                                :</label>
                                            <input type="text" id="name" name="name" class="form-control"
                                                placeholder="Enter Your Full Name" value="{{ $admin->name }}" />
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email Id <sup
                                                    class="text-danger fs-5">*</sup> :</label>
                                            {{-- @if ($admin->isStaff()) --}}
                                            @if (!$admin->isSuperAdmin())
                                                <input type="email" class="form-control" placeholder="Enter Email"
                                                    value="{{ $admin->email }}" disabled />
                                            @else
                                                <input type="email" id="email" name="email" class="form-control"
                                                    placeholder="Enter Email" value="{{ $admin->email }}" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="mobile_no" class="form-label">Mobile Number <sup
                                                    class="text-danger fs-5">*</sup> :</label>
                                            @if (!$admin->isSuperAdmin())
                                                <input type="text" id="mobile_no" name="mobile_no" class="form-control"
                                                    placeholder="Enter Mobile Number" value="{{ $admin->mobile }}"
                                                    readonly />
                                            @else
                                                <input type="text" id="mobile_no" name="mobile_no" class="form-control"
                                                    placeholder="Enter Mobile Number" value="{{ $admin->mobile }}" />
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @if (!$admin->isStaff())
                                <div class="col-lg-12">
                                    <div class="form-group mb-0 d-flex">
                                        <label for="name" class="form-label fw-bold">Two Factor Authentication Enable
                                            :</label>
                                        <div class="square-switch mx-3">
                                            <input type="checkbox" id="square-is_two_factor_auth_enabled"
                                                switch="is_two_factor_auth_enabled" name="is_two_factor_auth_enabled"
                                                value="1" {{ $admin->is_two_factor_auth_enabled ? 'checked' : '' }} />
                                            <label for="square-is_two_factor_auth_enabled" data-on-label="Yes"
                                                data-off-label="No"></label>
                                        </div>
                                    </div>
                                </div>
                                @endif --}}
                                    @if ($admin->type == 'employee')
                                        {{-- COMPANY NAME --}}
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                Company Name
                                            </label>
                                            <input type="text" name="company_name" class="form-control"
                                                value="{{ $admin->company_name }}">
                                        </div>

                                        {{-- AFFILIATE TYPE --}}
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                Affiliate Type
                                            </label>
                                            <select name="affiliate_type" class="form-control select2-class"
                                                data-placeholder="Select Affiliate Type">
                                                <option value=""></option>
                                                <option value="blogger"
                                                    {{ $admin->affiliate_type == 'blogger' ? 'selected' : '' }}>
                                                    Blogger</option>
                                                <option value="influencer"
                                                    {{ $admin->affiliate_type == 'influencer' ? 'selected' : '' }}>
                                                    Influencer
                                                </option>
                                                <option value="agency"
                                                    {{ $admin->affiliate_type == 'agency' ? 'selected' : '' }}>
                                                    Agency</option>
                                                <option value="publisher"
                                                    {{ $admin->affiliate_type == 'publisher' ? 'selected' : '' }}>
                                                    Publisher
                                                </option>
                                                <option value="other"
                                                    {{ $admin->affiliate_type == 'other' ? 'selected' : '' }}>
                                                    Other</option>
                                            </select>
                                        </div>

                                        {{-- TRAFFIC SOURCES --}}
                                        @php
                                            $trafficSources = $admin->traffic_sources ?? [];
                                        @endphp
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">
                                                    Traffic Sources <sup class="text-danger fs-5">*</sup> :
                                                </label>
                                                <select name="traffic_sources[]" class="form-control select2-class"
                                                    multiple data-placeholder="Select Traffic Sources">
                                                    <option value=""></option>
                                                    <option value="SEO"
                                                        {{ in_array('SEO', $trafficSources) ? 'selected' : '' }}>
                                                        SEO</option>
                                                    <option value="Google Ads"
                                                        {{ in_array('Google Ads', $trafficSources) ? 'selected' : '' }}>
                                                        Google
                                                        Ads
                                                    </option>
                                                    <option value="Facebook Ads"
                                                        {{ in_array('Facebook Ads', $trafficSources) ? 'selected' : '' }}>
                                                        Facebook Ads
                                                    </option>
                                                    <option value="Instagram"
                                                        {{ in_array('Instagram', $trafficSources) ? 'selected' : '' }}>
                                                        Instagram
                                                    </option>
                                                    <option value="YouTube"
                                                        {{ in_array('YouTube', $trafficSources) ? 'selected' : '' }}>
                                                        YouTube
                                                    </option>
                                                    <option value="LinkedIn"
                                                        {{ in_array('LinkedIn', $trafficSources) ? 'selected' : '' }}>
                                                        LinkedIn
                                                    </option>
                                                    <option value="Email Marketing"
                                                        {{ in_array('Email Marketing', $trafficSources) ? 'selected' : '' }}>
                                                        Email
                                                        Marketing</option>
                                                    <option value="WhatsApp Marketing"
                                                        {{ in_array('WhatsApp Marketing', $trafficSources) ? 'selected' : '' }}>
                                                        WhatsApp Marketing</option>
                                                    <option value="Telegram"
                                                        {{ in_array('Telegram', $trafficSources) ? 'selected' : '' }}>
                                                        Telegram
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- EXPECTED LEADS --}}
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                Expected Leads
                                            </label>
                                            <select name="expected_leads" class="form-control select2-class"
                                                data-placeholder="Select Expected Leads">
                                                <option value=""></option>
                                                <option value="less_than_50"
                                                    {{ $admin->expected_leads === 'less_than_50' ? 'selected' : '' }}>
                                                    Less Than 50
                                                </option>
                                                <option value="50_100"
                                                    {{ $admin->expected_leads === '50_100' ? 'selected' : '' }}>
                                                    50 - 100
                                                </option>
                                                <option value="100_500"
                                                    {{ $admin->expected_leads === '100_500' ? 'selected' : '' }}>
                                                    100 - 500
                                                </option>
                                                <option value="500_plus"
                                                    {{ $admin->expected_leads === '500_plus' ? 'selected' : '' }}>
                                                    500+
                                                </option>
                                            </select>
                                        </div>

                                        {{-- PROMOTION PLAN --}}
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label fw-bold">
                                                Promotion Plan
                                            </label>
                                            <textarea name="promotion_plan" rows="2" class="form-control" placeholder="Enter Promotion Plan">{{ $admin->promotion_plan }}</textarea>
                                        </div>
                                    @endif
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="address" class="form-label">Address:</label>
                                            <textarea id="address" name="address" class="form-control" placeholder="Enter Your Address">{{ $admin->address }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button id="updateBtn" type="button" class="btn btn-success waves-effect waves-light">Save
                            Changes</button>
                    </div>
                </form>
            </div>

            {{-- @if (!$admin->isStaff()) --}}
            @if (auth()->check())
                <div class="card border">
                    <form id="pswdFrm">
                        @csrf
                        <div class="card-header">
                            <h4 class="card-title mb-0">Change Password</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="current_password" class="form-label">Current Password <sup
                                                class="text-danger fs-5">*</sup> :</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" name="current_password" class="form-control"
                                                placeholder="Enter Current Password" />
                                            <button class="btn btn-light" type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label">New Password <sup class="text-danger fs-5">*</sup>
                                            :</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" name="new_password" class="form-control"
                                                placeholder="Enter Password" />
                                            <button class="btn btn-light" type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label">Confirm Password <sup class="text-danger fs-5">*</sup>
                                            :</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" name="new_password_confirmation" class="form-control"
                                                placeholder="Enter Confirm Password" />
                                            <button class="btn btn-light" type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button id="pswdBtn" type="button" class="btn btn-success waves-effect waves-light">Save
                                Changes</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(function() {
            $('.dropify').dropify().on('dropify.afterClear', function(event, element) {
                $(`[name="is_file_removed"]`).val('1');
            });

            $(document).on('click', '#updateBtn', function(e) {
                e.preventDefault();
                const $btn = $(this);

                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "{{ route('admin.profile.update') }}",
                    data: new FormData($('#profileFrm')[0]),
                    processData: false,
                    contentType: false,
                    beforeSend: () => {
                        $btn.attr('disabled', true);
                        showToastr();
                    },
                    error: (jqXHR, exception) => {
                        $btn.attr('disabled', false);
                        showToastr('error', formatErrorMessage(jqXHR, exception));
                    },
                    success: response => {
                        $btn.attr('disabled', false);
                        showToastr('success', response.message);
                    }
                });
            });

            $(document).on('click', '#pswdBtn', function(e) {
                e.preventDefault();
                const $btn = $(this);

                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: "{{ route('admin.profile.change.password') }}",
                    data: $('#pswdFrm').serialize(),
                    beforeSend: () => {
                        $btn.attr('disabled', true);
                        showToastr();
                    },
                    error: (jqXHR, exception) => {
                        $btn.attr('disabled', false);
                        showToastr('error', formatErrorMessage(jqXHR, exception));
                    },
                    success: response => {
                        $btn.attr('disabled', false);
                        showToastr('success', response.message);
                        $('#pswdFrm')[0].reset();
                    }
                });
            });
        });
    </script>
@endsection
