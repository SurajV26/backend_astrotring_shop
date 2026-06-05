@extends('layouts.master')

@section('title', 'Add Product')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between">

                <h4>Add Product</h4>

                <a href="{{ route('admin.products.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </div>
        </div>
    </div>

    <form id="createFrm" enctype="multipart/form-data">
        @csrf

        <div class="card">

            <div class="card-body">

                <ul class="nav nav-tabs">

                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#general">General</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#stone">Stone Details</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#specs">Specifications & FAQ</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#seo">SEO</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lab">Lab Certificates</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#images">Images</a>
                    </li>

                </ul>

                <div class="tab-content mt-3">

                    {{-- GENERAL TAB --}}
                    <div class="tab-pane fade show active" id="general">

                        <div class="row">

                            <div class="col-lg-8">

                                <div class="card">
                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">Product Code <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="text" name="code" class="form-control"
                                                    placeholder="Auto generated e.g. YASPUK-001" readonly required>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">Product Name <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="text" name="name" class="form-control"
                                                    placeholder="Example: Natural Yellow Sapphire (Pukhraj)" required>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">Slug <sup class="text-danger fs-5">*</sup> :</label>

                                                <input type="text" name="slug" class="form-control"
                                                    placeholder="Auto generated e.g. natural-yellow-sapphire" readonly
                                                    required>

                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Category <sup
                                                        class="text-danger fs-5">*</sup> :</label>
                                                <select name="category_id" class="form-control select2-class"
                                                    data-placeholder="Select Category" required>
                                                    <option value=""></option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}">
                                                            {{ $cat->code }} - {{ $cat->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-3 mb-3">

                                                <label class="fw-bold">Before Price</label>

                                                <input type="number" step="0.01" name="before_price"
                                                    class="form-control" placeholder="Example: 3500">

                                            </div>

                                            <div class="col-md-3 mb-3">

                                                <label class="fw-bold">After Price <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="number" step="0.01" name="after_price" class="form-control"
                                                    placeholder="Example: 2999" required>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">Stock Quantity <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="number" name="stock_qty" class="form-control"
                                                    placeholder="Example: 50" min="0" required>

                                            </div>

                                            <div class="col-md-12 mb-3">

                                                <label class="fw-bold">Description</label>

                                                <textarea name="description" class="form-control"
                                                    placeholder="Example: Certified natural Yellow Sapphire gemstone used in Vedic astrology for strengthening Jupiter and attracting prosperity."></textarea>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">
                                                    HSN Code
                                                </label>

                                                <input type="text" name="hsn_code" class="form-control"
                                                    placeholder="Example: 7103" value="7103">

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">
                                                    GST Rate (%)
                                                </label>

                                                <input type="number" step="0.01" name="gst_rate"
                                                    class="form-control" placeholder="Example: 0.5" value="0.5">

                                            </div>

                                            <div class="col-md-12 mt-3 mb-3">
                                                <h5 class="fw-bold">Shipping Details</h5>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Weight (grams)</label>
                                                <input type="number" step="1" name="weight" class="form-control"
                                                    placeholder="Example: 500">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Length (cm)</label>
                                                <input type="number" step="0.01" name="length" class="form-control"
                                                    placeholder="Example: 10">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Breadth (cm)</label>
                                                <input type="number" step="0.01" name="breadth" class="form-control"
                                                    placeholder="Example: 8">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Height (cm)</label>
                                                <input type="number" step="0.01" name="height" class="form-control"
                                                    placeholder="Example: 5">
                                            </div>

                                        </div>

                                    </div>
                                </div>

                            </div>

                            {{-- RIGHT SIDE --}}

                            <div class="col-lg-4">

                                <div class="card">

                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Product Status</h4>
                                    </div>

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <label>Status</label>

                                            <input type="hidden" name="status" value="0">

                                            <div class="square-switch">

                                                <input type="checkbox" id="switch-status" name="status" switch="status"
                                                    value="1" checked>

                                                <label for="switch-status" data-on-label="Yes"
                                                    data-off-label="No"></label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div class="card">

                                    <div class="card-header fw-bold">
                                        Primary Image
                                        <sup class="text-danger fs-5">*</sup>
                                        :
                                    </div>

                                    <div class="card-body">

                                        <input type="file" name="image" class="dropify" required>

                                        <small class="text-muted">
                                            Example: Upload front view gemstone image
                                        </small>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                    {{-- STONE DETAILS --}}
                    <div class="tab-pane fade" id="stone">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Stone Name</label>

                                <input type="text" name="stone_name" class="form-control"
                                    placeholder="Example: Yellow Sapphire / Pukhraj">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Planet</label>

                                <input type="text" name="planet" class="form-control"
                                    placeholder="Example: Jupiter (Guru)">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Origin</label>

                                <input type="text" name="origin" class="form-control"
                                    placeholder="Example: Sri Lanka">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Benefits</label>

                                <textarea name="benefits" class="form-control"
                                    placeholder="Example: Improves wisdom, wealth, career growth and strengthens Jupiter in horoscope."></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>How To Use</label>

                                <textarea name="how_to_use" class="form-control"
                                    placeholder="Example: Wear in gold ring on index finger on Thursday morning."></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Purity</label>

                                <textarea name="purity" class="form-control"
                                    placeholder="Example: 100% natural untreated gemstone with VVS clarity."></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Shipping Info</label>

                                <textarea name="shipping_info" class="form-control"
                                    placeholder="Example: Free delivery across India within 5-7 days."></textarea>
                            </div>

                            <label class="fw-bold d-flex justify-content-between">

                                <label class="fw-bold">Ratti</label>
                                <label class="fw-bold">Before Price</label>
                                <label class="fw-bold">After Price</label>

                                <button type="button" class="btn btn-sm btn-primary" id="add-ratti">
                                    <i class="fa fa-plus"></i>
                                </button>

                            </label>

                            <div id="ratti-wrapper"></div>

                        </div>
                    </div>

                    {{-- SPECIFICATIONS --}}
                    <div class="tab-pane fade" id="specs">

                        <label class="fw-bold d-flex justify-content-between">

                            Specifications

                            <button type="button" class="btn btn-sm btn-primary" id="add-spec">
                                <i class="fa fa-plus"></i>
                            </button>

                        </label>

                        <div id="spec-wrapper"></div>

                        <hr>

                        <label class="fw-bold d-flex justify-content-between">

                            FAQ

                            <button type="button" class="btn btn-sm btn-primary" id="add-faq">
                                <i class="fa fa-plus"></i>
                            </button>

                        </label>

                        <div id="faq-wrapper"></div>

                    </div>

                    {{-- SEO --}}
                    <div class="tab-pane fade" id="seo">

                        <div class="row">

                            <div class="col-md-12 mb-3">
                                <label>Meta Title</label>

                                <input type="text" name="meta_title" class="form-control"
                                    placeholder="Example: Buy Natural Yellow Sapphire Gemstone Online">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Meta Description</label>

                                <textarea name="meta_description" class="form-control"
                                    placeholder="Example: Premium natural Yellow Sapphire gemstone with certification and fast delivery."></textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Meta Keywords</label>

                                <input type="text" name="meta_keywords[]" class="form-control"
                                    placeholder="Example: yellow sapphire, pukhraj stone, guru ratna">
                            </div>

                        </div>
                    </div>

                    {{-- LAB CERTIFICATES --}}
                    <div class="tab-pane fade" id="lab">

                        <div class="card">

                            <div class="card-body">

                                <label class="fw-bold d-flex justify-content-between">

                                    Lab Certificates

                                    <button type="button" class="btn btn-sm btn-primary" id="add-lab">
                                        <i class="fa fa-plus"></i>
                                    </button>

                                </label>

                                <div id="lab-wrapper"></div>

                                <small class="text-muted">
                                    Example: Upload gemstone lab report and enter certificate number.
                                </small>

                            </div>

                        </div>

                    </div>

                    {{-- MEDIA --}}
                    <div class="tab-pane fade" id="images">

                        <div class="card">

                            <div class="card-body">

                                <label class="fw-bold">Gallery Media (Images / Videos)</label>

                                <input type="file" name="media[]" id="mediaInput" class="form-control" multiple
                                    accept="image/*,video/mp4,video/webm">

                                <small class="text-muted text-end">
                                    Select Multiple Media In One Time
                                </small>
                                <br>
                                <small class="text-muted">
                                    Example: Upload product images or video (JPG, PNG, WEBP, MP4, WEBM)
                                </small>

                                <div id="mediaPreview" class="mt-3 d-flex flex-wrap gap-3"></div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <div class="card-footer text-end">

                <button type="reset" class="btn btn-warning">
                    Clear
                </button>

                <button type="button" id="createBtn" class="btn btn-success">
                    Save
                </button>

            </div>

        </div>

    </form>

@endsection

@section('script')

    <script>
        $(document).ready(function() {

            $('.dropify').dropify();

            let rattiIndex = 0;
            let specIndex = 0;
            let faqIndex = 0;

            $('input[name="name"]').on('keyup change', function() {

                let slug = $(this).val().toLowerCase().trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                $('input[name="slug"]').val(slug);

            });

            $('#mediaInput').on('change', function(e) {

                let preview = $('#mediaPreview');
                preview.html('');

                let files = e.target.files;

                for (let i = 0; i < files.length; i++) {

                    let file = files[i];
                    let url = URL.createObjectURL(file);

                    if (file.type.startsWith('video/')) {

                        preview.append(`
                            <div>
                                <video width="180" controls>
                                    <source src="${url}">
                                </video>
                            </div>
                        `);

                    } else {

                        preview.append(`
                            <div>
                                <img src="${url}" width="180" class="img-fluid rounded">
                            </div>
                        `);

                    }

                }

            });

            $('#add-ratti').click(function() {

                $('#ratti-wrapper').append(`

                    <div class="row mb-2 ratti-item">

                        <div class="col-md-3">
                            <input type="number" step="0.01"
                            name="ratti_options[` + rattiIndex + `][ratti]"
                            class="form-control"
                            placeholder="Ratti (e.g. 5)">
                        </div>

                        <div class="col-md-3">
                            <input type="number" step="0.01"
                            name="ratti_options[` + rattiIndex + `][ratti_beforePrice]"
                            class="form-control"
                            placeholder="Before Price (e.g. 2999)">
                        </div>

                        <div class="col-md-4">
                            <input type="number" step="0.01"
                            name="ratti_options[` + rattiIndex + `][ratti_afterPrice]"
                            class="form-control"
                            placeholder="After Price (e.g. 1999)">
                        </div>

                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-ratti">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>

                    </div>

                `);

                rattiIndex++;

            });

            $(document).on('click', '.remove-ratti', function() {
                $(this).closest('.ratti-item').remove();
            });

            $('#add-spec').click(function() {

                $('#spec-wrapper').append(`

                    <div class="row mb-2 spec-item">

                    <div class="col-md-5">
                    <input type="text"
                    name="specifications[` + specIndex + `][title]"
                    class="form-control"
                    placeholder="Example: Color">
                    </div>

                    <div class="col-md-5">
                    <input type="text"
                    name="specifications[` + specIndex + `][value]"
                    class="form-control"
                    placeholder="Example: Yellow">
                    </div>

                    <div class="col-md-2">
                    <button type="button"
                    class="btn btn-danger remove-spec">
                    <i class="fa fa-times"></i>
                    </button>
                    </div>

                    </div>

                `);

                specIndex++;

            });

            $(document).on('click', '.remove-spec', function() {
                $(this).closest('.spec-item').remove();
            });

            $('#add-faq').click(function() {

                $('#faq-wrapper').append(`

                    <div class="faq-item mb-3">

                    <input type="text"
                    name="faq[` + faqIndex + `][question]"
                    class="form-control mb-2"
                    placeholder="Example: Who should wear Yellow Sapphire?">

                    <textarea
                    name="faq[` + faqIndex + `][answer]"
                    class="form-control"
                    placeholder="Example: People advised by astrologer to strengthen Jupiter."></textarea>

                    <button type="button"
                    class="btn btn-danger mt-2 remove-faq">
                    Remove
                    </button>

                    </div>

                `);

                faqIndex++;

            });

            $(document).on('click', '.remove-faq', function() {
                $(this).closest('.faq-item').remove();
            });

            $('#createBtn').click(function(e) {

                e.preventDefault();

                let formData = new FormData($('#createFrm')[0]);

                let btn = $(this);

                $.ajax({

                    url: "{{ route('admin.products.create') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,

                    beforeSend: () => {
                        btn.prop('disabled', true);
                        showToastr('info', 'Saving...');
                    },

                    success: res => {
                        showToastr('success', res.message);
                        window.location.href = "{{ route('admin.products.index') }}";
                    },

                    error: xhr => {
                        btn.prop('disabled', false);
                        showToastr('error', formatErrorMessage(xhr));
                    }

                });

            });

        });
    </script>
    <script>
        $(document).ready(function() {

            function generateProductCode() {

                let productName = $('input[name="name"]').val().trim();

                let categoryCode = $('select[name="category_id"] option:selected')
                    .text()
                    .split('-')[0]
                    .trim();

                if (!productName || !categoryCode) {
                    $('input[name="code"]').val('');
                    return;
                }

                $.ajax({
                    url: "/products/get-product-code",
                    type: "GET",
                    data: {
                        name: productName,
                        category: categoryCode
                    },
                    success: function(res) {
                        $('input[name="code"]').val(res.code);
                    }
                });
            }

            $('input[name="name"]').on('keyup change', generateProductCode);
            $('select[name="category_id"]').on('change', generateProductCode);

        });
    </script>
    <script>
        let labIndex = $('#lab-wrapper .lab-item').length;

        $('#add-lab').click(function() {

            $('#lab-wrapper').append(`

                <div class="row mb-3 lab-item">

                <div class="col-md-5">

                <input type="file"
                name="lab_certificates[` + labIndex + `][image]"
                class="form-control">

                </div>

                <div class="col-md-5">

                <input type="text"
                name="lab_certificates[` + labIndex + `][number]"
                class="form-control"
                placeholder="Example: GIA123456">

                </div>

                <div class="col-md-2">

                <button type="button"
                class="btn btn-danger remove-lab">
                <i class="fa fa-times"></i>
                </button>

                </div>

                </div>

            `);

            labIndex++;

        });

        $(document).on('click', '.remove-lab', function() {
            $(this).closest('.lab-item').remove();
        });
    </script>

@endsection
