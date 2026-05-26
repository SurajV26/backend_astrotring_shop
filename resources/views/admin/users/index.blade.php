@extends('layouts.master')

@section('title')
    Users
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">All Users</h4>

                <div class="page-title-right">
                    <a href="{{ route('admin.users.create.index') }}" class="btn btn-soft-info waves-effect waves-light">
                        <i class="fas fa-plus"></i> Create
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Filter by</h4>
                    <button id="reset-filter-btn" type="button" class="btn btn-light waves-effect waves-light"><i
                            class="fa fa-undo"></i> Reset</button>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col">
                            <div class="form-group">
                                <label class="form-label fw-bold">User</label>
                                <select id="user_id" class="form-control select2-class2" data-placeholder="Choose User">
                                    <option value=""></option>
                                    @foreach (\App\Models\User::where('type', 'user')->get() as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->code }} - {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label class="form-label fw-bold">Status</label>
                                <select id="status" class="form-control select2-class2" data-placeholder="Choose Status">
                                    <option value=""></option>
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- <div class="col">
                                        <div class="form-group">
                                            <label class="form-label fw-bold">Date Range</label>
                                            <input type="text" id="date_range" name="daterange" class="form-control"
                                                   placeholder="Choose Date Range" />
                                        </div>
                                    </div>  -->
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- USERS TABLE -->
    <div class="row">
        <div class="col-12">
            <div class="card border">
                <div class="card-body">

                    <table id="data-table" class="table table-bordered dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>User Details</th>
                                {{-- <th>Username</th> --}}
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th width="100px">Action</th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(function() {

            // STATUS CHANGE AJAX
            $('#data-table').on('change', '.change-status', function(e) {
                e.preventDefault();

                const id = $(this).data("id");
                const checkbox = $(this);
                checkbox.prop("disabled", true);

                $.get(`{{ route('admin.users.change.status') }}/${id}`, function(response) {
                    reloadTable('#data-table');
                    checkbox.prop("disabled", false);
                }).fail(function() {
                    checkbox.prop("disabled", false);
                });
            });

            const reloadTable = (tableId) => {
                if (!tableId.startsWith('#')) tableId = `#${tableId}`;
                $(tableId).DataTable().ajax.reload();
            };

            $(document).on('click', '#reset-filter-btn', function(e) {
                e.preventDefault();
                $('#user_id').val('').trigger('change');
                $('#status').val('1').trigger('change');
                reloadTable('#data-table');
            });

            // DATATABLE INITIALIZATION
            $('#data-table').DataTable({
                ajax: {
                    url: '{{ route('admin.users.list') }}',
                    data: function(d) {
                        d.user_id = $('#user_id').val();
                        d.status = $('#status').val();
                    }
                },

                columns: [{
                        data: 'code_name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'mobile'
                    },
                    {
                        data: null,
                        name: 'status',
                        className: 'text-center',
                        mRender: (data, type, row) => {
                            return `
                            @if (Can::is_accessible('users', 'update'))
                                <div class="square-switch">
                                    <input type="checkbox"
                                           id="status-switch-${row.id}"
                                           class="change-status"
                                           switch="status"
                                           data-id="${row.id}"
                                           ${row.status == 1 ? 'checked' : ''} />

                                    <label for="status-switch-${row.id}"
                                           data-on-label="Yes"
                                           data-off-label="No"></label>
                                </div>
                            @else
                                ${row.status == 1 ? 'Active' : 'Inactive'}
                            @endif
                        `;
                        }
                    },
                    {
                        data: null,
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        mRender: (data, type, row) => {
                            return `
                                <a href="{{ route('admin.users.update.index') }}/${row.id}"
                                   class="btn btn-soft-info btn-sm waves-effect waves-light">
                                    <i class="bx bx-pencil font-size-16"></i>
                                </a>
                                <a href="javascript:void(0);" data-href="{{ route('admin.users.view') }}/${row.id}" class="btn btn-soft-success btn-sm waves-effect waves-light open-remote-modal" data-target="#xxlRemoteModal"><i class="mdi mdi-eye font-size-16"></i></a>
                                <button type="button" class="btn btn-soft-danger btn-sm waves-effect waves-light delete-entry" data-href="{{ route('admin.users.delete') }}/${row.id}" data-tbl="data"><i class="bx bx-trash font-size-16"></i></button>
                        `;
                        }
                    }
                ]
            });

            $('#user_id, #status').on('change', function() {
                reloadTable('#data-table');
            });

        });
    </script>
@endsection
