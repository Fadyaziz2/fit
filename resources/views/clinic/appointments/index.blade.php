<x-app-layout>
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('message.user') }}</th>
                                        <th>{{ __('message.specialist') }}</th>
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.day') }}</th>
                                        <th>{{ __('message.start_time') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($appointments as $appointment)
                                        <tr>
                                            <td>{{ $appointment->id }}</td>
                                            <td>{{ $appointment->user?->display_name ?? $appointment->user?->email }}</td>
                                            <td>{{ $appointment->specialist?->name ?? '-' }}</td>
                                            <td>{{ $appointment->specialist?->branch?->name ?? '-' }}</td>
                                            <td>{{ $appointment->appointment_date }}</td>
                                            <td>{{ substr($appointment->appointment_time, 0, 5) }}</td>
                                            <td>
                                                <span class="badge {{ $appointment->status === 'completed' ? 'bg-success' : ($appointment->status === 'cancelled' ? 'bg-danger' : 'bg-secondary') }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.appointments.edit', $appointment) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">{{ __('message.no_results_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $appointments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
