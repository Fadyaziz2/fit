<x-app-layout>
    <div class="row" id="chat-dashboard"
         data-threads-endpoint="{{ route('chat.threads.index') }}"
         data-thread-endpoint="{{ route('chat.threads.show', ['thread' => '__THREAD__']) }}"
         data-thread-create-endpoint="{{ route('chat.threads.store') }}"
         data-user-search-endpoint="{{ route('chat.users.search') }}"
         data-message-endpoint="{{ route('chat.threads.messages.store', ['thread' => '__THREAD__']) }}"
         data-pusher-enabled="{{ $pusher['enabled'] ? '1' : '0' }}"
         data-pusher-key="{{ $pusher['key'] }}"
         data-pusher-cluster="{{ $pusher['cluster'] }}">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <h4 class="mb-0">{{ __('Conversations') }}</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-action="toggle-start-thread">
                            {{ __('Start conversation') }}
                        </button>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-action="refresh-threads">
                            {{ __('Refresh') }}
                        </button>
                    </div>
                </div>
                <div class="border-bottom p-3 d-none" id="chat-start-form-wrapper">
                    <form id="chat-start-form" class="row g-2" data-selected-user="">
                        <div class="col-12">
                            <label for="chat-start-user-input" class="form-label small text-muted mb-1">{{ __('Search for a user') }}</label>
                            <input type="hidden" id="chat-start-user-id" name="user_id">
                            <input type="text" class="form-control" id="chat-start-user-input" placeholder="{{ __('Type a name or email...') }}" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <div class="list-group" id="chat-start-suggestions" style="max-height: 200px; overflow-y: auto;"></div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-sm btn-primary" type="submit">{{ __('Open chat') }}</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-action="cancel-start-thread">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="chat-thread-list">
                        <div class="p-4 text-center text-muted" data-empty-state>
                            {{ __('No conversations yet.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mt-4 mt-lg-0">
            <div class="card h-100">
                <div class="card-header" id="chat-thread-header">
                    <h4 class="mb-0">{{ __('Select a conversation') }}</h4>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="border rounded bg-light flex-grow-1 overflow-auto p-3" id="chat-thread-messages">
                        <p class="text-muted text-center m-0" data-placeholder>{{ __('Choose a conversation to view messages.') }}</p>
                    </div>
                    <form id="chat-reply-form" class="mt-3" data-thread-id="">
                        <div class="input-group">
                            <textarea class="form-control" id="chat-reply-input" rows="1" placeholder="{{ __('Write a reply...') }}" disabled required></textarea>
                            <button class="btn btn-primary" type="submit" disabled>{{ __('Send') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            window.chatDashboardState = {
                authUser: {
                    id: {{ $authUser->id }},
                    name: @json($authUser->display_name ?? trim(($authUser->first_name . ' ' . $authUser->last_name)))
                }
            };
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.__chatDashboardStartEnhanced) {
                    return;
                }

                const root = document.getElementById('chat-dashboard');
                if (!root) {
                    return;
                }

                const threadCreateEndpoint = root.dataset.threadCreateEndpoint;
                const userSearchEndpoint = root.dataset.userSearchEndpoint;
                const startWrapper = document.getElementById('chat-start-form-wrapper');
                const startForm = document.getElementById('chat-start-form');
                const startInput = document.getElementById('chat-start-user-input');
                const startHiddenInput = document.getElementById('chat-start-user-id');
                const startSuggestions = document.getElementById('chat-start-suggestions');
                const startToggleBtn = root.querySelector('[data-action="toggle-start-thread"]');
                const startCancelBtn = root.querySelector('[data-action="cancel-start-thread"]');
                const startSubmitBtn = startForm?.querySelector('button[type="submit"]');
                const refreshBtn = root.querySelector('[data-action="refresh-threads"]');

                if (!threadCreateEndpoint || !startWrapper || !startForm) {
                    return;
                }

                window.__chatDashboardStartEnhanced = true;

                const renderUserSuggestions = (users) => {
                    if (!startSuggestions) {
                        return;
                    }

                    startSuggestions.innerHTML = '';

                    if (!users.length) {
                        const empty = document.createElement('div');
                        empty.className = 'list-group-item text-muted small';
                        empty.textContent = '{{ __('No users found.') }}';
                        startSuggestions.appendChild(empty);
                        return;
                    }

                    users.forEach((user) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = user.email ? `${user.name} (${user.email})` : user.name;
                        item.dataset.userId = user.id;
                        item.addEventListener('click', () => {
                            if (startHiddenInput) {
                                startHiddenInput.value = user.id;
                            }
                            startForm.setAttribute('data-selected-user', user.id);
                            if (startInput) {
                                startInput.value = user.email ? `${user.name} (${user.email})` : user.name;
                            }
                            if (startSuggestions) {
                                startSuggestions.innerHTML = '';
                            }
                        });
                        startSuggestions.appendChild(item);
                    });
                };

                let searchTimeout = null;

                const requestUserSuggestions = (term) => {
                    if (!userSearchEndpoint || !startSuggestions) {
                        return;
                    }

                    if (!term || term.length < 2) {
                        startSuggestions.innerHTML = '';
                        return;
                    }

                    axios.get(userSearchEndpoint, { params: { search: term } })
                        .then((response) => {
                            const data = response.data?.data || response.data || [];
                            renderUserSuggestions(Array.isArray(data) ? data : []);
                        });
                };

                const hideStartForm = () => {
                    startWrapper.classList.add('d-none');
                    startForm.reset();
                    startForm.setAttribute('data-selected-user', '');
                    if (startHiddenInput) {
                        startHiddenInput.value = '';
                    }
                    if (startSuggestions) {
                        startSuggestions.innerHTML = '';
                    }
                };

                startToggleBtn?.addEventListener('click', () => {
                    startWrapper.classList.toggle('d-none');
                    if (!startWrapper.classList.contains('d-none')) {
                        startInput?.focus();
                    }
                });

                startCancelBtn?.addEventListener('click', () => {
                    hideStartForm();
                });

                startInput?.addEventListener('input', (event) => {
                    startForm.setAttribute('data-selected-user', '');
                    if (startHiddenInput) {
                        startHiddenInput.value = '';
                    }

                    if (searchTimeout) {
                        clearTimeout(searchTimeout);
                    }

                    const value = event.target.value.trim();
                    searchTimeout = setTimeout(() => requestUserSuggestions(value), 250);
                });

                startForm.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const userId = startHiddenInput?.value || startForm.getAttribute('data-selected-user');
                    if (!userId) {
                        startInput?.focus();
                        return;
                    }

                    startSubmitBtn?.setAttribute('disabled', 'disabled');

                    axios.post(threadCreateEndpoint, { user_id: userId })
                        .then(() => {
                            hideStartForm();
                            refreshBtn?.click();
                        })
                        .finally(() => {
                            startSubmitBtn?.removeAttribute('disabled');
                        });
                });
            });
        </script>
        @if($pusher['enabled'])
            <script src="https://js.pusher.com/8.4/pusher.min.js" defer></script>
            <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.min.js" defer></script>
        @endif
        <script src="{{ mix('js/chat-dashboard.js') }}" defer></script>
    @endpush
</x-app-layout>
