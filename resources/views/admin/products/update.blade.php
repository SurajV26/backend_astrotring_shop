@extends('layouts.master')

@section('title', 'Update Product')

@section('content')

    <div class="row">
        <div class="col-12">

            <div class="page-title-box d-flex justify-content-between">
                <h4>Update Product</h4>

                <a href="{{ route('admin.products.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </div>

        </div>
    </div>

    <form id="updateFrm" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="id" value="{{ $product->id }}">

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
                        <a class="nav-link" data-bs-toggle="tab" href="#images">Images/Videos</a>
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
                                                <label class="form-label fw-bold">Product Code <sup
                                                        class="text-danger fs-5">*</sup>
                                                    :</label>
                                                <input type="text" name="code" class="form-control"
                                                    value="{{ $product->code }}" readonly required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Product Name <sup
                                                        class="text-danger fs-5">*</sup>
                                                    :</label>
                                                <input type="text" name="name" class="form-control"
                                                    value="{{ $product->name }}" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Slug <sup class="text-danger fs-5">*</sup>
                                                    :</label>
                                                <input type="text" name="slug" class="form-control"
                                                    value="{{ $product->slug }}" readonly required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Category <sup
                                                        class="text-danger fs-5">*</sup>
                                                    :</label>
                                                <select name="category_id" class="form-control select2-class" required>
                                                    <option value=""></option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}" @selected($product->category_id == $cat->id)>
                                                            {{ $cat->code }} - {{ $cat->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>


                                            <div class="col-md-3 mb-3">

                                                <label>Before Price</label>

                                                <input type="number" name="before_price" class="form-control"
                                                    value="{{ $product->before_price }}">

                                            </div>


                                            <div class="col-md-3 mb-3">

                                                <label>After Price <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="number" name="after_price" class="form-control"
                                                    value="{{ $product->after_price }}">

                                            </div>


                                            <div class="col-md-6 mb-3">

                                                <label>Stock Quantity <sup class="text-danger fs-5">*</sup>
                                                    :</label>

                                                <input type="number" name="stock_qty" class="form-control"
                                                    value="{{ $product->stock_qty }}">

                                            </div>

                                            <div class="col-md-12 mb-3">

                                                <label>Description</label>

                                                <textarea name="description" class="form-control">{{ $product->description }}</textarea>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="fw-bold">
                                                    HSN Code
                                                </label>

                                                <input type="text" name="hsn_code" class="form-control"
                                                    placeholder="Example: 7116" value="{{ $product->hsn_code }}">

                                            </div>

                                            <div class="col-md-6 mb-3">`

                                                <label class="fw-bold">
                                                    GST Rate (%)
                                                </label>

                                                <input type="number" step="0.01" name="gst_rate"
                                                    class="form-control" placeholder="Example: 3"
                                                    value="{{ $product->gst_rate }}">

                                            </div>

                                            <div class="col-md-12 mt-3 mb-3">
                                                <h5 class="fw-bold">Shipping Details</h5>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Weight (grams)</label>
                                                <input type="number" step="1" name="weight" class="form-control"
                                                    value="{{ $product->weight }}" placeholder="Example: 500">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Length (cm)</label>
                                                <input type="number" step="0.01" name="length" class="form-control"
                                                    value="{{ $product->length }}" placeholder="Example: 10">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Breadth (cm)</label>
                                                <input type="number" step="0.01" name="breadth" class="form-control"
                                                    value="{{ $product->breadth }}" placeholder="Example: 8">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label class="fw-bold">Height (cm)</label>
                                                <input type="number" step="0.01" name="height" class="form-control"
                                                    value="{{ $product->height }}" placeholder="Example: 5">
                                            </div>

                                        </div>

                                    </div>
                                </div>

                            </div>



                            <div class="col-lg-4">

                                <div class="card">

                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Product Specify</h4>
                                    </div>

                                    <div class="card-body">

                                        <label class="fw-bold">Sub Product Name <sup class="text-danger fs-5">*</sup>
                                            :</label>

                                        <input type="text" name="sub_name" class="form-control"
                                            placeholder="Example: Natural, Premium, Certified"
                                            value="{{ is_array($product->sub_name) ? implode(', ', $product->sub_name) : $product->sub_name }}"
                                            required>

                                    </div>

                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Product Status & Bestseller</h4>
                                    </div>

                                    {{-- STATUS --}}
                                    <div class="card-body">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <label class="fw-bold mb-0">Status</label>

                                            <input type="hidden" name="status" value="0">

                                            <div class="square-switch">
                                                <input type="checkbox" id="switch-status" name="status" switch="status"
                                                    value="1" {{ $product->status ? 'checked' : '' }}>

                                                <label for="switch-status" data-on-label="Yes" data-off-label="No">
                                                </label>
                                            </div>

                                        </div>

                                    </div>

                                    {{-- BESTSELLER --}}
                                    <div class="card-body">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <label class="fw-bold mb-0">Bestseller</label>

                                            <input type="hidden" name="is_bestseller" value="0">

                                            <div class="square-switch">
                                                <input type="checkbox" id="switch-bestseller" name="is_bestseller"
                                                    switch="is_bestseller" value="1"
                                                    {{ $product->is_bestseller ? 'checked' : '' }}>

                                                <label for="switch-bestseller" data-on-label="Yes" data-off-label="No">
                                                </label>
                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <div class="card">

                                    <div class="card-header fw-bold">
                                        Primary Image <sup class="text-danger fs-5">*</sup>
                                        :
                                    </div>

                                    <div class="card-body">

                                        <input type="file" name="image" class="dropify"
                                            data-default-file="{{ asset('storage/product/' . $product->image) }}">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- STONE TAB --}}
                    <div class="tab-pane fade" id="stone">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Stone Name</label>

                                <input type="text" name="stone_name" class="form-control"
                                    placeholder="Example: Yellow Sapphire / Pukhraj" value="{{ $product->stone_name }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Planet</label>

                                <input type="text" name="planet" class="form-control"
                                    placeholder="Example: Jupiter (Guru)" value="{{ $product->planet }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Origin</label>

                                <input type="text" name="origin" class="form-control"
                                    placeholder="Example: Sri Lanka" value="{{ $product->origin }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Benefits</label>

                                <textarea name="benefits" class="form-control"
                                    placeholder="Example: Improves wisdom, wealth, career growth and strengthens Jupiter in horoscope.">{{ $product->benefits }}</textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>How To Use</label>

                                <textarea name="how_to_use" class="form-control"
                                    placeholder="Example: Wear in gold ring on index finger on Thursday morning.">{{ $product->how_to_use }}</textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Purity</label>

                                <textarea name="purity" class="form-control"
                                    placeholder="Example: 100% natural untreated gemstone with VVS clarity.">{{ $product->purity }}</textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Shipping Info</label>

                                <textarea name="shipping_info" class="form-control"
                                    placeholder="Example: Free delivery across India within 5-7 days.">{{ $product->shipping_info }}</textarea>
                            </div>

                            <label class="fw-bold d-flex justify-content-between">

                                <label class="fw-bold">Ratti</label>
                                <label class="fw-bold">Before Price</label>
                                <label class="fw-bold">After Price</label>

                                <button type="button" class="btn btn-sm btn-primary" id="add-ratti">
                                    <i class="fa fa-plus"></i>
                                </button>

                            </label>

                            <div id="ratti-wrapper">

                                @foreach ($product->ratti_options ?? [] as $i => $ratti)
                                    <div class="row mb-2 ratti-item align-items-end">

                                        <div class="col-md-3">

                                            <input type="number" step="0.01"
                                                name="ratti_options[{{ $i }}][ratti]" class="form-control"
                                                value="{{ $ratti['ratti'] }}">
                                        </div>

                                        <div class="col-md-3">

                                            <input type="number" step="0.01"
                                                name="ratti_options[{{ $i }}][ratti_beforePrice]"
                                                class="form-control" value="{{ $ratti['ratti_beforePrice'] ?? '' }}">
                                        </div>

                                        <div class="col-md-4">

                                            <input type="number" step="0.01"
                                                name="ratti_options[{{ $i }}][ratti_afterPrice]"
                                                class="form-control" value="{{ $ratti['ratti_afterPrice'] ?? '' }}">
                                        </div>

                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger remove-ratti">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>

                                    </div>
                                @endforeach

                            </div>
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

                        <div id="spec-wrapper">

                            @foreach ($product->specifications ?? [] as $i => $spec)
                                <div class="row mb-2 spec-item align-items-end">

                                    <div class="col-md-5">
                                        <label class="fw-bold">Title</label>

                                        <input type="text" name="specifications[{{ $i }}][title]"
                                            class="form-control" value="{{ $spec['title'] ?? '' }}">
                                    </div>

                                    <div class="col-md-5">
                                        <label class="fw-bold">Value</label>

                                        <input type="text" name="specifications[{{ $i }}][value]"
                                            class="form-control" value="{{ $spec['value'] ?? '' }}">
                                    </div>

                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-spec">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>

                                </div>
                            @endforeach

                        </div>

                        <hr>

                        <label class="fw-bold d-flex justify-content-between">

                            FAQ

                            <button type="button" class="btn btn-sm btn-primary" id="add-faq">
                                <i class="fa fa-plus"></i>
                            </button>

                        </label>

                        <div id="faq-wrapper">

                            @foreach ($product->faq ?? [] as $i => $faq)
                                <div class="faq-item mb-3">

                                    <label class="fw-bold">Question</label>

                                    <input type="text" name="faq[{{ $i }}][question]"
                                        class="form-control mb-2" value="{{ $faq['question'] ?? '' }}">

                                    <label class="fw-bold">Answer</label>

                                    <textarea name="faq[{{ $i }}][answer]" class="form-control">{{ $faq['answer'] ?? '' }}</textarea>

                                    <button type="button" class="btn btn-danger mt-2 remove-faq">
                                        Remove
                                    </button>

                                </div>
                            @endforeach

                        </div>

                    </div>

                    {{-- SEO --}}
                    <div class="tab-pane fade" id="seo">

                        <div class="row">

                            <div class="col-md-12 mb-3">
                                <label>Meta Title</label>

                                <input type="text" name="meta_title" class="form-control"
                                    value="{{ $product->meta_title }}"
                                    placeholder="Example: Buy Natural Yellow Sapphire Gemstone Online">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Meta Description</label>

                                <textarea name="meta_description" class="form-control"
                                    placeholder="Example: Premium natural Yellow Sapphire gemstone with certification and fast delivery.">{{ $product->meta_description }}</textarea>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Meta Keywords</label>

                                <input type="text" name="meta_keywords[]" class="form-control"
                                    value="{{ is_array($product->meta_keywords) ? implode(',', $product->meta_keywords) : '' }}"
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

                                <div id="lab-wrapper">

                                    @foreach ($product->lab_certificates ?? [] as $i => $cert)
                                        <div class="row mb-3 lab-item align-items-end">

                                            <div class="col-md-5">

                                                <label class="fw-bold">Certificate Image</label>

                                                @if (!empty($cert['image']))
                                                    <img src="{{ asset('storage/lab_certificates/' . $cert['image']) }}"
                                                        width="120" class="mb-2">
                                                @endif

                                                <input type="file" name="lab_certificates[{{ $i }}][image]"
                                                    class="form-control">

                                            </div>

                                            <div class="col-md-5">

                                                <label class="fw-bold">Certificate Number</label>

                                                <input type="text"
                                                    name="lab_certificates[{{ $i }}][number]"
                                                    class="form-control" value="{{ $cert['number'] }}">

                                            </div>

                                            <div class="col-md-2">

                                                <button type="button" class="btn btn-danger remove-lab">
                                                    <i class="fa fa-times"></i>
                                                </button>

                                            </div>

                                        </div>
                                    @endforeach

                                </div>

                                <small class="text-muted">
                                    Example: Upload gemstone lab report and enter certificate number.
                                </small>

                            </div>

                        </div>

                    </div>

                    {{-- MEDIA TAB --}}
                    <div class="tab-pane fade" id="images">

                        <div class="card">

                            <div class="card-body">

                                <label>Upload New Media</label>

                                {{-- <input type="file" name="media[]" multiple class="form-control"> --}}
                                <input type="file" name="media[]" id="mediaInput" multiple class="form-control">

                                <hr>

                                <div class="row">

                                    @foreach ($product->images as $img)
                                        <div class="col-md-3 mb-3" id="img-{{ $img->id }}">

                                            @if (Str::contains($img->images, 'mp4'))
                                                <video width="100%" controls>

                                                    <source src="{{ asset('storage/product/' . $img->images) }}">

                                                </video>
                                            @else
                                                <img src="{{ asset('storage/product/' . $img->images) }}"
                                                    class="img-fluid">
                                            @endif


                                            <button type="button" class="btn btn-danger btn-sm remove-image mt-2"
                                                data-id="{{ $img->id }}">

                                                Delete

                                            </button>

                                        </div>
                                    @endforeach
                                    <div id="mediaPreview" class="mt-3 d-flex flex-wrap gap-3"></div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>


            <div class="card-footer text-end">

                <button type="button" id="updateBtn" class="btn btn-success">

                    Update Product

                </button>

            </div>

        </div>

    </form>

@endsection


@section('script')
    <script>
        let rattiIndex = {{ count($product->ratti_options ?? []) }};
        let specIndex = {{ count($product->specifications ?? []) }};
        let faqIndex = {{ count($product->faq ?? []) }};
        let labIndex = {{ count($product->lab_certificates ?? []) }};
    </script>
    <script>
        $(document).ready(function() {

            $('.dropify').dropify();

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
                placeholder="Example: Green">
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

            $('#updateBtn').click(function(e) {

                e.preventDefault();

                let formData = new FormData($('#updateFrm')[0]);
                let btn = $(this);

                $.ajax({
                    url: "{{ route('admin.products.update', $product->id) }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,

                    beforeSend: function() {
                        btn.prop('disabled', true);
                        btn.text('Updating...');
                        showToastr('info', 'Updating product...');
                    },

                    success: function(res) {
                        btn.prop('disabled', false);
                        btn.text('Update Product');

                        showToastr('success', res.message);

                        window.location.href = "{{ route('admin.products.index') }}";
                    },

                    error: function(xhr) {
                        btn.prop('disabled', false);
                        btn.text('Update Product');

                        showToastr('error', xhr.responseJSON?.message ?? 'Update failed');
                    }
                });

            });

        });
    </script>
    <script>
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
    <script>
        $(document).on('click', '.remove-image', function() {

            let id = $(this).data('id')

            $.ajax({

                url: "{{ route('admin.products.remove-image', '') }}/" + id,

                type: "DELETE",

                data: {
                    _token: "{{ csrf_token() }}"
                },

                success: function(res) {

                    $('#img-' + id).remove()

                    showToastr('success', res.message)

                }

            })

        })
    </script>

@endsection
