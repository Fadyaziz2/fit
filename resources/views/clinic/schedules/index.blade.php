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
                            <a href="{{ route('clinic.schedules.create') }}" class="btn btn-sm btn-primary">{{ __('message.add_form_title', ['form' => __('message.specialist_schedule')]) }}</a>
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
                                        <th>{{ __('message.specialist') }}</th>
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.day') }}</th>
                                        <th>{{ __('message.start_time') ?? 'Start Time' }}</th>
                                        <th>{{ __('message.end_time') ?? 'End Time' }}</th>
                                        <th>{{ __('message.duration') }}</th>
                                        <th class="text-end">{{ __('message.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($schedules as $schedule)
                                        <tr>
                                            <td>{{ $schedule->id }}</td>
                                            <td>{{ $schedule->specialist?->name ?? '-' }}</td>
                                            @php
                                                $branchNames = $schedule->specialist?->branches?->pluck('name')->filter()->implode(', ');
                                            @endphp
                                            <td>{{ $branchNames !== '' ? $branchNames : '-' }}</td>
                                            <td>{{ \Carbon\Carbon::create()->startOfWeek()->addDays($schedule->day_of_week)->translatedFormat('l') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</td>
                                            <td>{{ $schedule->slot_duration }} {{ __('message.minutes') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('clinic.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">{{ __('message.update') }}</a>
                                                <form action="{{ route('clinic.schedules.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('message.delete_msg') }}');">
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
                            {{ $schedules->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
