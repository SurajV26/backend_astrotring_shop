<div id="create-wrapper">
    <div class="modal-header">
        <h5 class="modal-title">Add Product Category</h5>
        <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body">
        <form id="addFrm" enctype="multipart/form-data">
            @csrf

            <div class="row">

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Product Category Code <sup class="text-danger">*</sup></label>
                        <input type="text" name="code" class="form-control"
                            placeholder="Enter Product Category Code" readonly required>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Product Category Name <sup class="text-danger">*</sup></label>
                        <input type="text" name="name" class="form-control"
                            placeholder="Enter Product Category Name" required>
                    </div>
                </div>

                <div class="col-lg-12 mt-3">
                    <div class="form-group">

                        <label class="fw-bold">
                            Category Image
                            <sup class="text-danger fs-5">*</sup> :
                        </label>

                        <input type="file" name="cat_image" class="dropify"
                            data-allowed-file-extensions="jpg jpeg png webp" data-max-file-size="2M">

                        <small class="text-muted">
                            Example: Upload Category Image (JPG, JPEG, PNG, WEBP)
                        </small>

                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="form-label">Slug <sup class="text-danger">*</sup></label>
                        <input type="text" name="slug" class="form-control"
                            placeholder="e.g. Auto generated from name" readonly>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <label class="form-label fw-bold">Status</label>
                        <input type="hidden" name="status" value="0">

                        <div class="square-switch">
                            <input type="checkbox" id="switch-status" name="status" switch="status" value="1"
                                checked>
                            <label for="switch-status" data-on-label="Yes" data-off-label="No"></label>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <div class="modal-footer">
        <button id="createBtn" type="button" class="btn btn-success">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>

<script>
    $('.dropify').dropify();

    // Auto generate slug from name
    $('input[name="name"]').on('keyup change', function() {
        let slug = $(this).val()
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '') // remove special chars
            .replace(/\s+/g, '-') // spaces → dash
            .replace(/-+/g, '-'); // multiple dashes

        $('input[name="slug"]').val(slug);
    });

    $('#create-wrapper').on('click', '#createBtn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const formData = new FormData($('#addFrm')[0]);

        $.ajax({
            url: "{{ route('admin.product_categories.create') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $btn.prop('disabled', true);
                showToastr('info', 'Saving...');
            },
            success: function(response) {
                showToastr('success', response.message);
                $('#data-table').DataTable().ajax.reload(null, false);
                $('#create-wrapper .close').click();
            },
            error: function(xhr) {
                showToastr('error', formatErrorMessage(xhr));
                $btn.prop('disabled', false);
            }
        });
    });
</script>
<script>
    $(document).ready(function() {

        function generateCategoryCode() {

            let categoryName = $('input[name="name"]').val().trim();

            if (!categoryName) {
                $('input[name="code"]').val('');
                return;
            }

            $.ajax({
                url: "/product_categories/get-category-code",
                type: "GET",
                data: {
                    name: categoryName
                },
                success: function(res) {
                    $('input[name="code"]').val(res.code);
                }
            });
        }

        $('input[name="name"]').on('keyup change', generateCategoryCode);

    });
</script>
