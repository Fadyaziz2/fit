<x-app-layout>
    <div class="row" id="chat-dashboard"
         data-threads-endpoint="{{ route('chat.threads.index') }}"
         data-thread-endpoint="{{ route('chat.threads.show', ['thread' => '__THREAD__']) }}"
         data-message-endpoint="{{ route('chat.threads.messages.store', ['thread' => '__THREAD__']) }}"
         data-pusher-enabled="{{ $pusher['enabled'] ? '1' : '0' }}"
         data-pusher-key="{{ $pusher['key'] }}"
         data-pusher-cluster="{{ $pusher['cluster'] }}">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('Conversations') }}</h4>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-action="refresh-threads">
                        {{ __('Refresh') }}
                    </button>
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
        <script src="{{ mix('js/chat-dashboard.js') }}"></script>
    @endpush
</x-app-layout>
