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
                            <a href="{{ route('clinic.branches.create') }}" class="btn btn-sm btn-primary" role="button">{{ __('message.add_form_title', ['form' => __('message.branch')]) }}</a>
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
                                        <th>{{ __('message.contact_number') }}</th>
                                        <th>{{ __('message.email') }}</th>
                                        <th>{{ __('message.address') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branches as $branch)
                                        <tr>
                                            <td>{{ $branch->id }}</td>
                                            <td>{{ $branch->name }}</td>
                                            <td>{{ $branch->phone ?? '-' }}</td>
                                            <td>{{ $branch->email ?? '-' }}</td>
                                            <td>{{ $branch->address ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                                <form action="{{ route('clinic.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('message.delete_msg') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('message.delete') ?? __('message.delete_form_title', ['form' => '']) }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('message.no_results_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $branches->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
