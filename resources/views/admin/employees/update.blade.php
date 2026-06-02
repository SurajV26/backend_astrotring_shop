@extends('layouts.master')

@section('title', 'Update Employee')

@section('content')

    <div class="row">
        <div class="col-12">

            <div class="page-title-box d-flex justify-content-between align-items-center">

                <h4 class="mb-0">
                    Update Employee
                </h4>

                <a href="{{ route('admin.employees.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </div>

        </div>
    </div>

    <form id="updateFrm" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="id" value="{{ $employee->id }}">

        <div class="row">

            {{-- LEFT SIDE --}}
            <div class="col-lg-8">

                <div class="card">

                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            Employee Details
                        </h4>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            {{-- NAME --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Name
                                    <sup class="text-danger">*</sup>
                                </label>

                                <input type="text" name="name" class="form-control" placeholder="Enter Name"
                                    value="{{ $employee->name }}">

                            </div>

                            {{-- USERNAME --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Username
                                    <sup class="text-danger">*</sup>
                                </label>

                                <input type="text" name="username" class="form-control" placeholder="Enter Username"
                                    value="{{ $employee->username }}">

                            </div>

                            {{-- EMAIL --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Email
                                    <sup class="text-danger">*</sup>
                                </label>

                                <input type="email" name="email" class="form-control" placeholder="Enter Email"
                                    value="{{ $employee->email }}">

                            </div>

                            {{-- COUNTRY CODE --}}
                            <div class="col-md-2 mb-3">

                                <label class="form-label fw-bold">
                                    Code
                                </label>

                                <input type="text" name="country_code" class="form-control"
                                    value="{{ $employee->country_code ?? '+91' }}">

                            </div>

                            {{-- MOBILE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label fw-bold">
                                    Mobile
                                </label>

                                <input type="text" name="mobile" class="form-control" placeholder="Enter Mobile"
                                    value="{{ $employee->mobile }}">

                            </div>

                            {{-- DATE OF JOINING --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Date Of Joining
                                </label>

                                <input type="date" name="date_of_joining" class="form-control"
                                    value="{{ $employee->date_of_joining ? \Carbon\Carbon::parse($employee->date_of_joining)->format('Y-m-d') : '' }}">

                            </div>

                            {{-- COMMISSION PERCENTAGE --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Commission Percentage
                                </label>
                                <input type="number" name="commission_percentage" class="form-control"
                                    placeholder="Enter Commission Percentage" min="0" max="100"
                                    value="{{ $employee->commission_percentage ?? 5 }}">

                            </div>

                            {{-- PASSWORD --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="password" class="form-label fw-bold">Password <sup
                                            class="text-danger fs-5">*</sup> :</label>
                                    <div class="input-group auth-pass-inputgroup">
                                        <input type="password" id="password" name="password" class="form-control"
                                            placeholder="Enter Password" />
                                        <button class="btn btn-light" type="button" id="password-addon"><i
                                                class="mdi mdi-eye-outline"></i></button>
                                        <label class="text-danger fw-bold">
                                            Password (Leave blank to keep unchanged)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            {{-- CONFIRM PASSWORD --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="cnfrm_password" class="form-label fw-bold">Confirm Password <sup
                                            class="text-danger fs-5">*</sup> :</label>
                                    <div class="input-group auth-pass-inputgroup">
                                        <input type="password" id="cnfrm_password" name="password_confirmation"
                                            class="form-control" placeholder="Enter Password Again" />
                                        <button class="btn btn-light" type="button" id="password-addon"><i
                                                class="mdi mdi-eye-outline"></i></button>
                                    </div>
                                </div>
                            </div>

                            {{-- COMPANY NAME --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Company Name
                                </label>
                                <input type="text" name="company_name" class="form-control"
                                    value="{{ $employee->company_name }}">
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
                                        {{ $employee->affiliate_type == 'blogger' ? 'selected' : '' }}>
                                        Blogger</option>
                                    <option value="influencer"
                                        {{ $employee->affiliate_type == 'influencer' ? 'selected' : '' }}>Influencer
                                    </option>
                                    <option value="agency" {{ $employee->affiliate_type == 'agency' ? 'selected' : '' }}>
                                        Agency</option>
                                    <option value="publisher"
                                        {{ $employee->affiliate_type == 'publisher' ? 'selected' : '' }}>Publisher</option>
                                    <option value="other" {{ $employee->affiliate_type == 'other' ? 'selected' : '' }}>
                                        Other</option>
                                </select>
                            </div>

                            {{-- TRAFFIC SOURCES --}}
                            @php
                                $trafficSources = $employee->traffic_sources ?? [];
                            @endphp
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">
                                        Traffic Sources <sup class="text-danger fs-5">*</sup> :
                                    </label>
                                    <select name="traffic_sources[]" class="form-control select2-class" multiple
                                        data-placeholder="Select Traffic Sources">
                                        <option value=""></option>
                                        <option value="SEO" {{ in_array('SEO', $trafficSources) ? 'selected' : '' }}>
                                            SEO</option>
                                        <option value="Google Ads"
                                            {{ in_array('Google Ads', $trafficSources) ? 'selected' : '' }}>Google Ads
                                        </option>
                                        <option value="Facebook Ads"
                                            {{ in_array('Facebook Ads', $trafficSources) ? 'selected' : '' }}>Facebook Ads
                                        </option>
                                        <option value="Instagram"
                                            {{ in_array('Instagram', $trafficSources) ? 'selected' : '' }}>Instagram
                                        </option>
                                        <option value="YouTube"
                                            {{ in_array('YouTube', $trafficSources) ? 'selected' : '' }}>YouTube</option>
                                        <option value="LinkedIn"
                                            {{ in_array('LinkedIn', $trafficSources) ? 'selected' : '' }}>LinkedIn</option>
                                        <option value="Email Marketing"
                                            {{ in_array('Email Marketing', $trafficSources) ? 'selected' : '' }}>Email
                                            Marketing</option>
                                        <option value="WhatsApp Marketing"
                                            {{ in_array('WhatsApp Marketing', $trafficSources) ? 'selected' : '' }}>
                                            WhatsApp Marketing</option>
                                        <option value="Telegram"
                                            {{ in_array('Telegram', $trafficSources) ? 'selected' : '' }}>Telegram</option>
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
                                        {{ $employee->expected_leads === 'less_than_50' ? 'selected' : '' }}>
                                        Less Than 50
                                    </option>
                                    <option value="50_100" {{ $employee->expected_leads === '50_100' ? 'selected' : '' }}>
                                        50 - 100
                                    </option>
                                    <option value="100_500"
                                        {{ $employee->expected_leads === '100_500' ? 'selected' : '' }}>
                                        100 - 500
                                    </option>
                                    <option value="500_plus"
                                        {{ $employee->expected_leads === '500_plus' ? 'selected' : '' }}>
                                        500+
                                    </option>
                                </select>
                            </div>

                            {{-- PROMOTION PLAN --}}
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">
                                    Promotion Plan
                                </label>
                                <textarea name="promotion_plan" rows="2" class="form-control" placeholder="Enter Promotion Plan">{{ $employee->promotion_plan }}</textarea>
                            </div>

                            {{-- ADDRESS --}}
                            <div class="col-12 mb-3">

                                <label class="form-label fw-bold">
                                    Address
                                </label>

                                <textarea name="address" rows="2" class="form-control" placeholder="Enter Address">{{ $employee->address }}</textarea>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- RIGHT SIDE --}}
            <div class="col-lg-4">

                {{-- STATUS --}}
                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Employee Status
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="form-group d-flex justify-content-between align-items-center mb-4">

                            <label class="form-label fw-bold mb-0">
                                Status
                            </label>

                            <input type="hidden" name="status" value="0">

                            <div class="square-switch">

                                <input type="checkbox" id="switch-status" name="status" switch="status" value="1"
                                    {{ $employee->status == 1 ? 'checked' : '' }}>

                                <label for="switch-status" data-on-label="Yes" data-off-label="No">
                                </label>

                            </div>

                        </div>

                        {{-- BUTTONS --}}
                        <div class="d-grid gap-2">

                            <button type="button" id="updateBtn" class="btn btn-success">

                                Update

                            </button>

                            <button type="reset" class="btn btn-warning">

                                Clear

                            </button>

                        </div>

                    </div>

                </div>

                {{-- PROFILE IMAGE --}}
                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Employee Image
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-sm-12">

                                <div class="form-group">

                                    <input type="file" name="profile_image" class="dropify" accept="image/*"
                                        @if ($employee->profile_image) data-default-file="{{ asset('storage/user/' . $employee->profile_image) }}" @endif />

                                    <small class="text-muted">
                                        <b>Example::</b>
                                        image size - 250x250.
                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

@endsection

@section('script')

    <script>
        $(document).ready(function() {

            $('.dropify').dropify();

            $('#updateBtn').click(function(e) {

                e.preventDefault();

                let btn = $(this);

                let formData = new FormData($('#updateFrm')[0]);

                $.ajax({

                    url: "{{ route('admin.employees.update') }}",

                    type: "POST",

                    data: formData,

                    processData: false,

                    contentType: false,

                    beforeSend: () => {

                        btn.prop('disabled', true);

                        showToastr('info', 'Updating...');
                    },

                    success: (res) => {

                        showToastr('success', res.message);

                        window.location.href =
                            "{{ route('admin.employees.index') }}";
                    },

                    error: (xhr) => {

                        btn.prop('disabled', false);

                        showToastr(
                            'error',
                            formatErrorMessage(xhr)
                        );
                    }

                });

            });

        });
    </script>

@endsection
