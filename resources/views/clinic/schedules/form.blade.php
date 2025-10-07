<x-app-layout>
    <div>
        <?php $schedule = $schedule ?? null; ?>
        <form method="POST" action="{{ $schedule ? route('clinic.schedules.update', $schedule) : route('clinic.schedules.store') }}">
            @csrf
            @if($schedule)
                @method('PUT')
            @endif
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="header-title">
                                <h4 class="card-title">{{ $pageTitle }}</h4>
                            </div>
                            <div class="card-action">
                                <a href="{{ route('clinic.schedules.index') }}" class="btn btn-sm btn-primary">{{ __('message.back') }}</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.specialist') }} <span class="text-danger">*</span></label>
                                    <select name="specialist_id" class="form-select" required>
                                        <option value="">{{ __('message.select_name', ['select' => __('message.specialist')]) }}</option>
                                        @foreach($specialists as $specialist)
                                            @php
                                                $branchNames = $specialist->branches->pluck('name')->filter()->implode(', ');
                                            @endphp
                                            <option value="{{ $specialist->id }}" {{ (string) $specialist->id === (string) old('specialist_id', $schedule->specialist_id ?? '') ? 'selected' : '' }}>
                                                {{ $specialist->name }}@if($branchNames) ({{ $branchNames }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('specialist_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.day') }} <span class="text-danger">*</span></label>
                                    <select name="day_of_week" class="form-select" required>
                                        @for($i = 0; $i < 7; $i++)
                                            <?php $label = \Carbon\Carbon::create()->startOfWeek()->addDays($i)->translatedFormat('l'); ?>
                                            <option value="{{ $i }}" {{ (string) $i === (string) old('day_of_week', $schedule->day_of_week ?? 0) ? 'selected' : '' }}>{{ $label }}</option>
                                        @endfor
                                    </select>
                                    @error('day_of_week')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.start_time') }}</label>
                                    <input type="time" name="start_time" class="form-control" value="{{ old('start_time', isset($schedule) ? substr($schedule->start_time, 0, 5) : '') }}" required>
                                    @error('start_time')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.end_time') }}</label>
                                    <input type="time" name="end_time" class="form-control" value="{{ old('end_time', isset($schedule) ? substr($schedule->end_time, 0, 5) : '') }}" required>
                                    @error('end_time')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('message.duration') }} ({{ __('message.minutes') }})</label>
                                    <input type="number" name="slot_duration" class="form-control" value="{{ old('slot_duration', $schedule->slot_duration ?? 30) }}" min="5" required>
                                    @error('slot_duration')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">{{ $schedule ? __('message.update') : __('message.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
