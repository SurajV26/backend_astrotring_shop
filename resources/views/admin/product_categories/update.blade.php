<div id="update-wrapper">
    <div class="modal-header">
        <h5 class="modal-title">Edit Product Category</h5>
        <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body">
        <form id="updateFrm" enctype="multipart/form-data">
            @csrf

            <div class="row">

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Product Category Code <sup class="text-danger">*</sup></label>
                        <input type="text" name="code" class="form-control" value="{{ $category->code }}"
                            placeholder="Enter Product Category Code" readonly>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Product Category Name <sup class="text-danger">*</sup></label>
                        <input type="text" name="name" class="form-control" value="{{ $category->name }}"
                            placeholder="Enter Product Category Name">
                    </div>
                </div>

                <div class="col-lg-12 mt-3">
                    <div class="form-group">

                        <label class="fw-bold">
                            Category Image
                            <sup class="text-danger fs-5">*</sup> :
                        </label>

                        <input type="file" name="cat_image" class="dropify"
                            data-default-file="{{ $category->cat_image ? asset('storage/categories/' . $category->cat_image) : '' }}"
                            data-allowed-file-extensions="jpg jpeg png webp" data-max-file-size="2M">

                        <small class="text-muted">
                            Example: Upload Category Image (JPG, JPEG, PNG, WEBP).
                        </small>

                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Product Category Slug <sup class="text-danger">*</sup></label>
                        <input type="text" name="slug" class="form-control" value="{{ $category->slug }}"
                            readonly>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <label class="form-label fw-bold">Status</label>
                        <input type="hidden" name="status" value="0">
                        <div class="square-switch">
                            <input type="checkbox" id="switch-status" name="status" switch="status" value="1"
                                {{ $category->status ? 'checked' : '' }}>
                            <label for="switch-status" data-on-label="Yes" data-off-label="No"></label>
                        </div>
                    </div>
                </div>
        </form>
    </div>

    <div class="modal-footer">
        <button id="updateBtn" type="button" class="btn btn-success">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>

<script>
    $('.dropify').dropify();

    $('input[name="name"]').on('keyup change', function() {
        let slug = $(this).val()
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');

        $('input[name="slug"]').val(slug);
    });

    // Submit update
    $('#update-wrapper').on('click', '#updateBtn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        let formData = new FormData($('#updateFrm')[0]);

        $.ajax({
            url: "{{ route('admin.product_categories.update', $category->id) }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $btn.prop('disabled', true);
                showToastr('info', 'Updating...');
            },
            success: function(res) {
                showToastr('success', res.message);
                $('#data-table').DataTable().ajax.reload(null, false);
                $('#update-wrapper .close').click();
            },
            error: function(xhr) {
                showToastr('error', formatErrorMessage(xhr));
                $btn.prop('disabled', false);
            }
        });
    });
</script>
