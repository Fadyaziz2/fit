@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.permission_check').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var container = this.closest('td');

                if (!container) {
                    return;
                }

                var select = container.querySelector('.scope-select');

                if (select) {
                    select.disabled = !this.checked;
                }
            });
        });
    });
</script>
@endpush
<x-app-layout :assets="$assets ?? []">
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('message.list') }}</h5>
                        </div>
                        <div class="text-center ms-3 ms-lg-0 ms-md-0">
                            @if($auth_user->can('permission-add'))
                                <a href="#" class="float-end btn btn-sm btn-primary" data-modal-form="form" data-size="small" data--href="{{ route('permission.add',[ 'type' => 'permission' ]) }}" data-app-title="{{ __('message.add_form_title',['form' => __('message.permission')]) }}" data-placement="top">{{ __('message.add_form_title', ['form' => __('message.permission')] ) }}</a>
                            @endif
                        </div>
                    </div>       
                    <div class="card-body">
                        <div class="accordion" id="permissionAccordion">
                        {{ html()->form('POST', route('permission.store'))->open() }} 
                            @foreach ($permissions as $parentpermission)
                                @if(is_null($parentpermission['parent_id']))
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $parentpermission['id'] }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $parentpermission['id'] }}" aria-expanded="true" aria-controls="collapse{{ $parentpermission['id'] }}">
                                            {{ $parentpermission['title'] }}
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $parentpermission['id'] }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $parentpermission['id'] }}" data-bs-parent="#permissionAccordion">
                                        <div class="sticky-header">
                                            <input type="submit" name="Save" value="{{ __('message.save') }}" class="btn btn-md btn-primary ">
                                        </div>
                                        <div class="accordion-body">
                                                <table class="table text-center table-bordered bg_white">
                                                        <tr>
                                                            <th>{{ __('message.name') }}</th>
                                                            @foreach($parentpermission->subpermission as $p)
                                                                <th class="text-center">{{ $p->title }}</th>
                                                            @endforeach
                                                        </tr>
                                                        @foreach($roles as $role)
                                                        <tr>
                                                            <td>{{ ucwords(str_replace('_',' ',$role->name)) }}</td>
                                                            @foreach($parentpermission->subpermission as $p)
                                                                @php
                                                                    $isScopedPermission = in_array($p->name, $scopedPermissions ?? [], true);
                                                                    $isChecked = AuthHelper::checkRolePermission($role, $p->name);
                                                                    $currentScope = $roleScopes[$role->id][$p->name] ?? \App\Models\RolePermissionScope::SCOPE_ALL;
                                                                @endphp
                                                                <td>
                                                                    <div class="d-flex flex-column align-items-center gap-2">
                                                                        <input class="form-check-input checkbox no-wh permission_check"
                                                                            id="permission-{{$role->id}}-{{$p->id}}"
                                                                            type="checkbox"
                                                                            name="permission[{{$p->name}}][]"
                                                                            value='{{$role->name}}'
                                                                            {{ $isChecked ? 'checked' : '' }}
                                                                            @if($role->is_hidden) disabled @endif >
                                                                        @if($isScopedPermission)
                                                                            <select class="form-select form-select-sm scope-select" name="permission_scope[{{$p->name}}][{{$role->name}}]" @if(!$isChecked) disabled @endif>
                                                                                @foreach($scopeOptions ?? [] as $value => $label)
                                                                                    <option value="{{ $value }}" @selected($currentScope === $value)>{{ $label }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                        @endforeach
                                                        {{-- @foreach ($roles as $role)
                                                        <tr>
                                                            <td>{{ $role['title'] }}</td> 
                                                            @foreach ($permissions as $permission)
                                                                <td class="text-center">
                                                                    <input class="form-check-input" type="checkbox" id="role-{{$role['id']}}-permission-{{$permission['id']}}" 
                                                                        name="permission[{{$role['name']}}][]" value="{{ $permission['name'] }}"
                                                                        {{ (AuthHelper::checkRolePermission($role, $permission->name)) ? 'checked' : '' }}>
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach --}}
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        {{ html()->form()->close() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
