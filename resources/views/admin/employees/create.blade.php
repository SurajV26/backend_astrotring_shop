@extends('layouts.master')

@section('title', 'Create Employee')

@section('content')

    {{-- PAGE HEADER --}}
    <div class="row">
        <div class="col-12">

            <div class="page-title-box d-flex justify-content-between align-items-center">

                <h4 class="mb-0">
                    Create Employee
                </h4>

                <a href="{{ route('admin.employees.index') }}" class="btn btn-primary">

                    <i class="fas fa-arrow-left"></i>
                    Back

                </a>

            </div>

        </div>
    </div>

    <form id="createFrm" enctype="multipart/form-data">
        @csrf

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

                                <input type="text" name="name" class="form-control" placeholder="Enter Name">

                            </div>

                            {{-- USERNAME --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Username
                                    <sup class="text-danger">*</sup>
                                </label>

                                <input type="text" name="username" class="form-control" placeholder="Enter Username">

                            </div>

                            {{-- EMAIL --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Email
                                    <sup class="text-danger">*</sup>
                                </label>

                                <input type="email" name="email" class="form-control" placeholder="Enter Email">

                            </div>

                            {{-- COUNTRY CODE/MOBILE --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">
                                        Mobile <sup class="text-danger fs-5">*</sup> :
                                    </label>

                                    <div class="input-group">
                                        <select name="country_code" id="country_code" class="form-select"
                                            style="max-width:120px">
                                            <option value="">Loading...</option>
                                        </select>

                                        <input type="text" name="mobile" class="form-control" maxlength="10"
                                            placeholder="Enter Mobile No">
                                    </div>
                                </div>
                            </div>

                            {{-- DATE OF JOINING --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Date Of Joining
                                </label>

                                <input type="date" name="date_of_joining" class="form-control">

                            </div>

                            {{-- COMMISSION PERCENTAGE --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Commission Percentage
                                </label>

                                <input type="number" name="commission_percentage" class="form-control"
                                    placeholder="Enter Commission Percentage" min="0" max="100">

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
                                    placeholder="Enter Company Name">
                            </div>

                            {{-- AFFILIATE TYPE --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Affiliate Type
                                </label>
                                <select name="affiliate_type" class="form-control select2-class"
                                    data-placeholder="Select Affiliate Type">
                                    <option value=""></option>
                                    <option value="blogger">Blogger</option>
                                    <option value="influencer">Influencer</option>
                                    <option value="agency">Agency</option>
                                    <option value="publisher">Publisher</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            {{-- TRAFFIC SOURCES --}}
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">
                                        Traffic Sources <sup class="text-danger fs-5">*</sup> :
                                    </label>
                                    <select name="traffic_sources[]" class="form-control select2-class" multiple
                                        data-placeholder="Select Traffic Sources">
                                        <option value=""></option>
                                        <option value="SEO">SEO</option>
                                        <option value="Google Ads">Google Ads</option>
                                        <option value="Facebook Ads">Facebook Ads</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="YouTube">YouTube</option>
                                        <option value="LinkedIn">LinkedIn</option>
                                        <option value="Email Marketing">Email Marketing</option>
                                        <option value="WhatsApp Marketing">WhatsApp Marketing</option>
                                        <option value="Telegram">Telegram</option>
                                        <option value="Other">Other</option>
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
                                    <option value="less_than_50">Less Than 50</option>
                                    <option value="50_100">50 - 100</option>
                                    <option value="100_500">100 - 500</option>
                                    <option value="500_plus">500+</option>
                                </select>
                            </div>

                            {{-- PROMOTION PLAN --}}
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">
                                    Promotion Plan
                                </label>
                                <textarea name="promotion_plan" rows="2" class="form-control" placeholder="Enter Promotion Plan"></textarea>
                            </div>

                            {{-- ADDRESS --}}
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">
                                    Address
                                </label>
                                <textarea name="address" rows="2" class="form-control" placeholder="Enter Address"></textarea>
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
                                    checked>

                                <label for="switch-status" data-on-label="Yes" data-off-label="No">
                                </label>

                            </div>

                        </div>

                        {{-- BUTTONS --}}
                        <div class="d-grid gap-2">

                            <button type="button" id="createBtn" class="btn btn-success">

                                Save

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

                                    <input type="file" name="profile_image" class="dropify" accept="image/*" />

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

            $('#createBtn').click(function(e) {

                e.preventDefault();

                let btn = $(this);

                let formData = new FormData($('#createFrm')[0]);

                $.ajax({

                    url: "{{ route('admin.employees.create') }}",

                    type: "POST",

                    data: formData,

                    processData: false,

                    contentType: false,

                    beforeSend: () => {

                        btn.prop('disabled', true);

                        showToastr('info', 'Saving...');
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
    <script>
        fetch('https://restcountries.com/v3.1/all?fields=name,idd,cca2')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('country_code');
                select.innerHTML = '';

                data.forEach(country => {
                    if (country.idd && country.idd.root && country.idd.suffixes) {
                        country.idd.suffixes.forEach(suffix => {
                            const code = country.idd.root + suffix;

                            const option = document.createElement('option');
                            option.value = code;
                            option.textContent = `${code} (${country.cca2})`;

                            // India default
                            if (code === '+91') {
                                option.selected = true;
                            }

                            select.appendChild(option);
                        });
                    }
                });
            })
            .catch(error => {
                console.error('Country code API error:', error);
            });
    </script>

@endsection
