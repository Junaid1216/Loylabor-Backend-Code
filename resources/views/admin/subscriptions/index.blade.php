@extends('admin.master_layout')

@section('title')
    <title>{{ __('Subscription Plans') }}</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>{{ __('Subscription Plans') }}</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4>{{ __('Subscription Plans List') }}</h4>
                            <a class="btn btn-primary" href="{{ route('admin.subscriptions.create') }}">
                                <i class="fa fa-plus"></i> {{ __('Add New') }}
                            </a>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="subscriptionTable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('SN') }}</th>
                                            <th>{{ __('Plan Name') }}</th>
                                            <th>{{ __('Duration') }}</th>
                                            <th>{{ __('Price (PKR)') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($subscriptions as $index => $plan)
                                        <tr>
                                            <td>{{ $subscriptions->firstItem() + $index }}</td>
                                            <td>{{ $plan->name }}</td>
                                            <td>{{ $plan->duration_months }} Months</td>
                                            <td>{{ number_format($plan->price_pkr, 2) }}</td>
                                            <td>
                                                @if($plan->is_active)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a class="btn btn-info btn-sm" href="{{ route('admin.subscriptions.show', $plan->id) }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a class="btn btn-primary btn-sm" href="{{ route('admin.subscriptions.edit', $plan->id) }}">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.subscriptions.destroy', $plan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                        </div>

                        <div class="card-footer text-center">
                            {{ $subscriptions->links() }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </section>
</div>
@endsection
