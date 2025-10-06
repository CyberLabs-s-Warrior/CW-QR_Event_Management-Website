@extends('layouts.layout')

@section('title', $isEdit ? 'Edit User' : 'Tambah User')

@section('content')
    <div class="dashboard-main-body">
        <x-breadcrumb>
            <x-slot:title>{{ $isEdit ? 'Edit User' : 'Tambah User' }}</x-slot:title>
            <x-slot:icon>solar:shield-user-broken</x-slot:icon>
        </x-breadcrumb>

       @include('layouts.feedback')

        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $isEdit ? 'Edit User' : 'Tambah User' }}</h5>
                </div>
                <div class="card-body">

                    <form action="{{ $isEdit ? route('admin.user.update', $user->id) : route('admin.user.store') }}"
                        method="POST" class="row gy-3 needs-validation" novalidate>
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <div class="icon-field has-validation">
                                <span class="icon">
                                    <iconify-icon icon="f7:person"></iconify-icon>
                                </span>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                    class="form-control" placeholder="Enter Name" required>
                                @error('name')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <div class="icon-field has-validation">
                                <span class="icon">
                                    <iconify-icon icon="mage:email"></iconify-icon>
                                </span>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                    class="form-control" placeholder="Enter Email" required>
                                @error('email')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                                {{-- <div class="invalid-feedback">
                                Please provide email address
                            </div> --}}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <div class="icon-field has-validation">
                                <span class="icon">
                                    <iconify-icon icon="solar:phone-calling-linear"></iconify-icon>
                                </span>
                                <input type="number" name="phone_number"
                                    value="{{ old('phone_number', $user->phone_number) }}" class="form-control"
                                    placeholder="+62 000-0000" required>
                                @error('phone_number')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                                {{-- <div class="invalid-feedback">
                                Please provide phone number
                            </div> --}}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <div class="icon-field has-validation" style="position: relative;">
                                <span class="icon"
                                    style="position: absolute; left: 2px; top: 50%; transform: translateY(-50%);">
                                    <iconify-icon icon="oui:app-users-roles"></iconify-icon>
                                </span>
                                <select name="role" class="form-select" style="padding-left: 40px;" required>
                                    <option value="" class="">Pilih Role</option>
                                    @if (auth()->user()->id !== 1)
                                        <option value="super_admin" disabled
                                            {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin
                                        </option>
                                    @else
                                        <option value="super_admin"
                                            {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin
                                        </option>
                                    @endif
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                                        Admin
                                    </option>
                                </select>
                                @error('role')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                                {{-- <div class="invalid-feedback">
                                Please provide role
                            </div> --}}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="icon-field has-validation">
                                <span class="icon">
                                    <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                                </span>
                                <input type="password" name="password" class="form-control" placeholder="*******" required
                                    {{ $isEdit ? '' : 'required' }}>
                                @if ($isEdit)
                                    <small>Biarkan kosong jika tidak ingin mengganti password</small>
                                @endif
                                @error('password')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                                {{-- <div class="invalid-feedback">
                                Please provide password
                            </div> --}}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <div class="icon-field has-validation">
                                <span class="icon">
                                    <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                                </span>
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="*******" required {{ $isEdit ? '' : 'required' }}>
                                @if ($isEdit)
                                    <small>Biarkan kosong jika tidak ingin mengganti password</small>
                                @endif
                                @error('password_confirmation')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                                {{-- <div class="invalid-feedback">
                                Please confirm password
                            </div> --}}
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button class="btn btn-primary-600" type="submit">{{ $isEdit ? 'Update' : 'Simpan' }}</button>
                            <a href="{{ route('admin.user.index') }}" class="btn btn-danger-600">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
