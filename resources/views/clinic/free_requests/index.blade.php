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
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.contact_number') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th>{{ __('message.created_at') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $request)
                                        <tr>
                                            <td>{{ $request->id }}</td>
                                            <td>{{ $request->user?->display_name ?? $request->user?->email }}</td>
                                            <td>{{ $request->branch?->name ?? '-' }}</td>
                                            <td>{{ $request->phone }}</td>
                                            <td>
                                                <span class="badge {{ $request->status === 'converted' ? 'bg-success' : ($request->status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                    {{ ucfirst($request->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at?->format('Y-m-d H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.free_requests.edit', $request) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">{{ __('message.no_results_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $requests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
