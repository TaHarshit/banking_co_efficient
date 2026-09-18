@extends('layouts.app')
@section('pagewisestyle')
    <link rel="stylesheet" href="{{ url('assets/vendor/dropify/dropify.min.css') }}">
@endsection
@section('pagewisescript')
    <script src="{{ url('assets/vendor/dropify/dropify.min.js') }}"></script>
@endsection
@section('customjs')
    <script type="text/javascript">
        $('.dropify').dropify();

        // Auto-fill quota and end date when plan is selected
        $('#plan_id').on('change', function() {
            var selected = $(this).find(':selected');
            var quota = selected.data('quota');
            var validity = selected.data('validity');
            var validityType = selected.data('validity-type') || 'month';

            if (quota && (!$('#user_quota').val() || $('#user_quota').val() == '0')) {
                $('#user_quota').val(quota);
            }

            // If start date is set and end date is empty, compute end date
            var startDateVal = $('#subscription_start_date').val();
            if (startDateVal && validity && !$('#subscription_end_date').val()) {
                var d = new Date(startDateVal);
                if (validityType === 'year') {
                    d.setFullYear(d.getFullYear() + parseInt(validity));
                } else if (validityType === 'day') {
                    d.setDate(d.getDate() + parseInt(validity));
                } else {
                    d.setMonth(d.getMonth() + parseInt(validity));
                }
                var month = ('0' + (d.getMonth() + 1)).slice(-2);
                var day = ('0' + d.getDate()).slice(-2);
                $('#subscription_end_date').val(d.getFullYear() + '-' + month + '-' + day);
            }
        });
    </script>
@endsection
@include('partials.headerfiles')
@include('partials.footerfiles')
@section('content')
    @include('partials.navbar')
    @include('partials.sidebar')
    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1>{{ isset($data) ? 'Edit Business' : 'Add Business' }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('managebusinesses') }}">Manage Businesses</a></li>
                    <li class="breadcrumb-item">{{ isset($data) ? 'Edit Business' : 'Add Business' }}</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ isset($data) ? 'Edit Business' : 'Add Business' }}</h5>
                            <form class="row g-3 needs-validation" action="{{ route('storebusiness') }}" method="POST"
                                enctype="multipart/form-data" novalidate>
                                @csrf
                                <input type="hidden" name="id" value="{{ isset($data) ? $data->id : '0' }}">

                                <div class="col-12 position-relative">
                                    <label for="name" class="form-label">Business Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name"
                                        value="{{ isset($data) ? $data->name : old('name') }}">
                                    @if ($errors->has('name'))
                                        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email"
                                        value="{{ isset($data) ? $data->email : old('email') }}">
                                    @if ($errors->has('email'))
                                        <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                    @endif
                                    @if (!isset($data))
                                        <small class="text-muted">An invitation email will be sent to this address to set up
                                            password.</small>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="logo" class="form-label">Logo</label>
                                    <div class="{{ $errors->has('logo') ? 'is-invalid' : '' }}">
                                        <input type="file" name="logo"
                                            class="dropify {{ $errors->has('logo') ? 'is-invalid' : '' }}" id="logo"
                                            data-default-file="{{ isset($data) && $data->logo && Storage::exists('public/business_logos/' . $data->logo) ? asset('storage/business_logos/' . $data->logo) : '' }}">
                                    </div>
                                    <label class="pl-1 mt-1 col-md-12 col-lg-12">Recommended size: 200x200px (JPG,
                                        PNG)</label>
                                    @if ($errors->has('logo'))
                                        <div class="invalid-feedback">{{ $errors->first('logo') }}</div>
                                    @endif
                                </div>

                                <div class="col-12 position-relative">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea name="address" id="address" rows="3"
                                        class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}">{{ isset($data) ? $data->address : old('address') }}</textarea>
                                    @if ($errors->has('address'))
                                        <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                                    @endif
                                </div>

                                <hr class="my-4">
                                <h5 class="card-title text-primary"><i class="bi bi-credit-card-2-front me-2"></i>Cash Subscription & Employee Quota</h5>

                                <div class="col-md-6 position-relative">
                                    <label for="plan_id" class="form-label">Subscription Plan</label>
                                    <select name="plan_id" id="plan_id" class="form-select {{ $errors->has('plan_id') ? 'is-invalid' : '' }}">
                                        <option value="">-- Select Plan --</option>
                                        @if(isset($plans))
                                            @foreach($plans as $plan)
                                                <option value="{{ $plan->id }}" 
                                                    data-quota="{{ $plan->user_quota }}"
                                                    data-validity="{{ $plan->validity }}"
                                                    data-validity-type="{{ $plan->validity_type }}"
                                                    {{ (isset($data) && $data->plan_id == $plan->id) || old('plan_id') == $plan->id ? 'selected' : '' }}>
                                                    {{ $plan->name }} ({{ $plan->type == 1 ? 'Business' : 'Individual' }} - ${{ number_format($plan->price, 2) }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('plan_id'))
                                        <div class="invalid-feedback">{{ $errors->first('plan_id') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="user_quota" class="form-label">Employee Quota (Seats)</label>
                                    <input type="number" name="user_quota" id="user_quota" min="0" class="form-control {{ $errors->has('user_quota') ? 'is-invalid' : '' }}"
                                        value="{{ isset($data) ? $data->user_quota : old('user_quota', 0) }}" placeholder="Max employees allowed">
                                    <small class="text-muted">Maximum number of employees the business can add under their account.</small>
                                    @if ($errors->has('user_quota'))
                                        <div class="invalid-feedback">{{ $errors->first('user_quota') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="subscription_start_date" class="form-label">Subscription Start Date</label>
                                    <input type="date" name="subscription_start_date" id="subscription_start_date" 
                                        class="form-control {{ $errors->has('subscription_start_date') ? 'is-invalid' : '' }}"
                                        value="{{ isset($data) && $data->subscription_start_date ? \Carbon\Carbon::parse($data->subscription_start_date)->format('Y-m-d') : old('subscription_start_date', date('Y-m-d')) }}">
                                    @if ($errors->has('subscription_start_date'))
                                        <div class="invalid-feedback">{{ $errors->first('subscription_start_date') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="subscription_end_date" class="form-label">Subscription End Date</label>
                                    <input type="date" name="subscription_end_date" id="subscription_end_date" 
                                        class="form-control {{ $errors->has('subscription_end_date') ? 'is-invalid' : '' }}"
                                        value="{{ isset($data) && $data->subscription_end_date ? \Carbon\Carbon::parse($data->subscription_end_date)->format('Y-m-d') : old('subscription_end_date') }}">
                                    @if ($errors->has('subscription_end_date'))
                                        <div class="invalid-feedback">{{ $errors->first('subscription_end_date') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="payment_mode" class="form-label">Payment Mode</label>
                                    <select name="payment_mode" id="payment_mode" class="form-select {{ $errors->has('payment_mode') ? 'is-invalid' : '' }}">
                                        <option value="cash" {{ (isset($data) && $data->payment_mode == 'cash') || old('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank_transfer" {{ (isset($data) && $data->payment_mode == 'bank_transfer') || old('payment_mode') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cheque" {{ (isset($data) && $data->payment_mode == 'cheque') || old('payment_mode') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="other" {{ (isset($data) && $data->payment_mode == 'other') || old('payment_mode') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @if ($errors->has('payment_mode'))
                                        <div class="invalid-feedback">{{ $errors->first('payment_mode') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="payment_notes" class="form-label">Cash Payment Notes / Receipt Reference</label>
                                    <textarea name="payment_notes" id="payment_notes" rows="2" class="form-control {{ $errors->has('payment_notes') ? 'is-invalid' : '' }}"
                                        placeholder="e.g. Cash collected by admin, receipt #1234">{{ isset($data) ? $data->payment_notes : old('payment_notes') }}</textarea>
                                    @if ($errors->has('payment_notes'))
                                        <div class="invalid-feedback">{{ $errors->first('payment_notes') }}</div>
                                    @endif
                                </div>

                                @if (isset($data))
                                    <div class="col-12 position-relative">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status"
                                            class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}">
                                            <option value="1"
                                                {{ isset($data) && $data->status == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0"
                                                {{ isset($data) && $data->status == 0 ? 'selected' : '' }}>Inactive
                                            </option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                                        @endif
                                    </div>
                                @endif

                                <div class="col-sm-10 mt-4">
                                    <button type="submit"
                                        class="btn btn-primary">{{ isset($data) ? 'Save Changes' : 'Add Business' }}</button>
                                    <a href="{{ route('managebusinesses') }}" class="btn btn-secondary ms-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')
@endsection
