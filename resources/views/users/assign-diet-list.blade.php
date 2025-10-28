@if( count($data) > 0 )
    @foreach ($data as $assigndiet)
        @php
            $assignment = $assigndiet->userAssignDiet->first();
            $serveTimes = collect(optional($assignment)->serve_times)->filter()->values();
            $planDetails = $dietPrintPlans[$assigndiet->id] ?? [];
            $startDate = optional($assignment)->start_date;
            $startDateDisplay = $startDate ? $startDate->format('Y-m-d') : null;
        @endphp
        <tr data-plan='@json($planDetails, JSON_UNESCAPED_UNICODE)'
            data-diet-id="{{ $assigndiet->id }}"
            data-start-date="{{ $startDate ? $startDate->toDateString() : '' }}"
            data-start-date-display="{{ $startDateDisplay ?? '' }}">
            <td><img src="{{ getSingleMedia($assigndiet, 'diet_image') }}" alt="diet-image" class="bg-soft-primary rounded img-fluid avatar-40 me-3"></td>
            <td>
                <div class="d-flex flex-column">
                    <span>{{ $assigndiet->title }}</span>
                    @if($startDateDisplay)
                        <small class="text-muted diet-start-date-text">{{ __('message.diet_start_date_display', ['date' => $startDateDisplay]) }}</small>
                    @endif
                    @if(!empty(optional($assignment)->custom_plan))
                        <span class="badge bg-soft-primary text-dark mt-1 align-self-start">{{ __('message.custom_meal_plan_badge') }}</span>
                    @endif
                </div>
            </td>
            <td>
                @if($serveTimes->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($serveTimes as $index => $time)
                            <span class="badge bg-soft-primary text-dark">
                                {{ __('message.meal_time_number', ['number' => $index + 1]) }}: {{ $time }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span>-</span>
                @endif
            </td>
            <td class="text-nowrap">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-print-diet-id="{{ $assigndiet->id }}" data-bs-toggle="tooltip" title="{{ __('message.print_single_diet') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 9V5H8V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M16 15H18C19.1046 15 20 14.1046 20 13V11C20 9.89543 19.1046 9 18 9H6C4.89543 9 4 9.89543 4 11V13C4 14.1046 4.89543 15 6 15H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8 12H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M16 15V19H8V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <a class="btn btn-sm btn-icon btn-success" data-modal-form="form" data-size="large"
                        data-bs-toggle="tooltip"
                        href="#"
                        data--href="{{ route('edit.assigndiet', ['user_id' => $user_id, 'diet_id' => $assigndiet->id]) }}"
                        data-app-title="{{ __('message.update_form_title',[ 'form' => __('message.meal_plan') ]) }}"
                        title="{{ __('message.edit_meals') }}">
                        <span class="btn-inner">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </span>
                    </a>
                    <a class="btn btn-sm btn-icon btn-danger"
                        data-bs-toggle="tooltip" href="{{ route('delete.assigndiet', [ 'diet_id' => $assigndiet->id, 'user_id' => $user_id ]) }}"
                        data--confirmation='true'
                        data--ajax='true'
                        data-title="{{ __('message.delete_form_title',[ 'form'=> __('message.assigndiet') ]) }}"
                        title="{{ __('message.delete_form_title',[ 'form'=>  __('message.assigndiet') ]) }}"
                        data-message='{{ __("message.delete_msg") }}'>
                        <span class="btn-inner">
                            <svg width="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" stroke="currentColor"> <path d="M19.3248 9.46826C19.3248 9.4682618.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path
                                    d="M17.4406 6.23973C16.6556 6.23973 15.9796 5.68473 15.8256 4.91573L15.5826 3.69973C15.4326 3.13873 14.9246 2.75073 14.3456 2.75073H10.1126C9.53358 2.75073 9.02558 3.13873 8.87558 3.69973L8.63258 4.91573C8.47858 5.68473 7.80258 6.23973 7.01758 6.23973"
                                    stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </span>
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="4">
            {{ __('message.not_found_entry', [ 'name' => __('message.diet') ]) }}
        </td>
    </tr>
@endif

