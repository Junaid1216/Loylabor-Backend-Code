@extends('admin.master_layout')
@section('title')
    <title>Bookings</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Bookings Management</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Reference Code</th>
                                        <th>Customer</th>
                                        <th>Technician</th>
                                        <th>Status</th>
                                        <th>Date/Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                    <tr>
                                        <td>
                                            @if($booking->booking_reference)
                                                <span class="badge badge-warning font-weight-bold" style="font-size:14px; letter-spacing:1px;">
                                                    {{ $booking->booking_reference }}
                                                </span>
                                            @else
                                                <span class="text-muted">Pending confirmation</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($booking->customer)->name }}</td>
                                        <td>{{ optional($booking->technician)->name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $booking->status === 'accepted' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $booking->service_date }} {{ $booking->time_slot }}</td>
                                        <td>
                                            <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No bookings yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $bookings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
