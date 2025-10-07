<x-app-layout>
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ route('clinic.specialists.create') }}" class="btn btn-sm btn-primary" role="button">{{ __('message.add_form_title', ['form' => __('message.specialist')]) }}</a>
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
                                        <th>{{ __('message.name') }}</th>
                                        <th>{{ __('message.specialist') }}</th>
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.contact_number') }}</th>
                                        <th>{{ __('message.email') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($specialists as $specialist)
                                        <tr>
                                            <td>{{ $specialist->id }}</td>
                                            <td>{{ $specialist->name }}</td>
                                            <td>{{ $specialist->specialty ?? '-' }}</td>
                                            @php
                                                $branchNames = $specialist->branches->pluck('name')->filter()->implode(', ');
                                            @endphp
                                            <td>{{ $branchNames !== '' ? $branchNames : '-' }}</td>
                                            <td>{{ $specialist->phone ?? '-' }}</td>
                                            <td>{{ $specialist->email ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ $specialist->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $specialist->is_active ? __('message.active') : __('message.inactive') }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.specialists.edit', $specialist) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                                <form action="{{ route('clinic.specialists.destroy', $specialist) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('message.delete_msg') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('message.delete') }}</button>
                                                </form>
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
                            {{ $specialists->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
