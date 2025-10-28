<x-app-layout>
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFreeRequestModal">
                                {{ __('message.add_button_form', ['form' => __('message.free_booking_request')]) }}
                            </button>
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

    <div class="modal fade" id="createFreeRequestModal" tabindex="-1" aria-labelledby="createFreeRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFreeRequestModalLabel">{{ __('message.add_form_title', ['form' => __('message.free_booking_request')]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('message.close') }}"></button>
                </div>
                <form method="POST" action="{{ route('clinic.free_requests.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="free-request-name" class="form-label">{{ __('message.full_name') }}</label>
                            <input type="text" name="full_name" id="free-request-name" value="{{ old('full_name') }}" class="form-control @error('full_name') is-invalid @enderror" required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="free-request-phone" class="form-label">{{ __('message.contact_number') }}</label>
                            <input type="text" name="phone" id="free-request-phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @php
                            $selectedBranchId = old('branch_id', $defaultBranchId);
                        @endphp
                        <div class="mb-3">
                            <label for="free-request-branch" class="form-label">{{ __('message.branch') }}</label>
                            @if($branches->count() > 1)
                                <select name="branch_id" id="free-request-branch" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">{{ __('message.select_name', ['select' => __('message.branch')]) }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            @elseif($branches->count() === 1)
                                @php $singleBranch = $branches->first(); @endphp
                                <input type="hidden" name="branch_id" value="{{ $singleBranch->id }}">
                                <input type="text" class="form-control" value="{{ $singleBranch->name }}" disabled>
                            @else
                                <select name="branch_id" id="free-request-branch" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">{{ __('message.select_name', ['select' => __('message.branch')]) }}</option>
                                </select>
                            @endif
                            @error('branch_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('message.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('message.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    @if($errors->has('full_name') || $errors->has('phone') || $errors->has('branch_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('createFreeRequestModal');
                if (modalElement) {
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush
