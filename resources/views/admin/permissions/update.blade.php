@extends('layouts.master')

@section('title') Permissions @endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">
                Permissions Update - {{ $user->name }}
            </h4>

            <div class="page-title-right">
                <a href="{{ route('admin.employees.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">

        <form method="POST" action="{{ route('admin.permissions.update', $user->id) }}">
            @csrf

            @php
            $permissions = json_decode($user->permissions ?? '[]');

            $allPermissions = [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-home'],
                ['key' => 'product_stocks', 'label' => 'Product Stock', 'icon' => 'fas fa-boxes'],
                ['key' => 'users', 'label' => 'Users', 'icon' => 'fas fa-users'],
                ['key' => 'delivery_rates', 'label' => 'Delivery Rates', 'icon' => 'fas fa-truck'],
                ['key' => 'coupons', 'label' => 'Coupons', 'icon' => 'fas fa-tags'],
                ['key' => 'product_categories', 'label' => 'Product Category', 'icon' => 'fas fa-th-large'],
                ['key' => 'products', 'label' => 'Products', 'icon' => 'fas fa-box'],
                ['key' => 'orders', 'label' => 'Orders', 'icon' => 'fas fa-shopping-cart'],
                ['key' => 'returns', 'label' => 'Returns', 'icon' => 'fas fa-undo'],
                ['key' => 'store_banners', 'label' => 'Store Banner', 'icon' => 'fas fa-images'],
            ];
            @endphp

            <div class="row">

                @foreach($allPermissions as $perm)
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center">
                                <div class="me-3 text-primary">
                                    <i class="{{ $perm['icon'] }} fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">{{ $perm['label'] }}</h6>
                                </div>
                            </div>

                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                    value="{{ $perm['key'] }}" id="perm_{{ $perm['key'] }}"
                                    {{ in_array($perm['key'], $permissions) ? 'checked' : '' }}>
                            </div>

                        </div>
                    </div>
                </div>
                @endforeach

            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>

        </form>

    </div>
</div>

@endsection