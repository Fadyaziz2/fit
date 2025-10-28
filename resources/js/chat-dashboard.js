require('./bootstrap');

let EchoInstance = null;

const ensureEcho = (root) => {
    if (EchoInstance !== null) {
        return EchoInstance;
    }

    if (!root || root.dataset.pusherEnabled !== '1') {
        EchoInstance = false;
        return EchoInstance;
    }

    if (typeof window.Echo === 'undefined' || typeof window.Pusher === 'undefined') {
        console.warn('Real-time chat dependencies are not loaded.');
        EchoInstance = false;
        return EchoInstance;
    }

    EchoInstance = new window.Echo({
        broadcaster: 'pusher',
        key: root.dataset.pusherKey,
        cluster: root.dataset.pusherCluster || 'mt1',
        forceTLS: true,
    });

    return EchoInstance;
};

const escapeHtml = (unsafe) => {
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('chat-dashboard');
    if (!root) {
        return;
    }

    const threadsEndpoint = root.dataset.threadsEndpoint;
    const threadEndpointTemplate = root.dataset.threadEndpoint;
    const messageEndpointTemplate = root.dataset.messageEndpoint;
    const threadCreateEndpoint = root.dataset.threadCreateEndpoint;
    const userSearchEndpoint = root.dataset.userSearchEndpoint;

    const state = {
        threads: [],
        subscriptions: new Map(),
        activeThreadId: null,
    };

    window.__chatDashboardStartEnhanced = true;

    const listEl = document.getElementById('chat-thread-list');
    const headerEl = document.getElementById('chat-thread-header');
    const messagesEl = document.getElementById('chat-thread-messages');
    const formEl = document.getElementById('chat-reply-form');
    const textareaEl = document.getElementById('chat-reply-input');
    const sendBtn = formEl.querySelector('button[type="submit"]');
    const refreshBtn = root.querySelector('[data-action="refresh-threads"]');
    const startWrapper = document.getElementById('chat-start-form-wrapper');
    const startForm = document.getElementById('chat-start-form');
    const startInput = document.getElementById('chat-start-user-input');
    const startHiddenInput = document.getElementById('chat-start-user-id');
    const startSuggestions = document.getElementById('chat-start-suggestions');
    const startToggleBtn = root.querySelector('[data-action="toggle-start-thread"]');
    const startCancelBtn = root.querySelector('[data-action="cancel-start-thread"]');
    const startSubmitBtn = startForm?.querySelector('button[type="submit"]');
    let startSearchTimeout = null;

    const resolveThreadSortDate = (thread) => {
        const candidate = thread.last_message_at || thread.updated_at || thread.created_at || 0;
        return new Date(candidate);
    };

    const renderThreads = () => {
        listEl.innerHTML = '';

        if (!state.threads.length) {
            const empty = document.createElement('div');
            empty.className = 'p-4 text-center text-muted';
            empty.textContent = 'No conversations yet.';
            listEl.appendChild(empty);
            return;
        }

        state.threads.forEach((thread) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
            item.dataset.threadId = thread.id;

            const content = document.createElement('div');
            content.className = 'me-3 flex-grow-1';
            const title = document.createElement('div');
            title.className = 'fw-semibold';
            title.textContent = thread.user?.name || `#${thread.id}`;

            const subtitle = document.createElement('div');
            subtitle.className = 'text-muted small';
            const lastMessageAt = formatDate(thread.last_message_at);
            subtitle.textContent = lastMessageAt ? lastMessageAt : 'No messages yet.';

            content.appendChild(title);
            content.appendChild(subtitle);

            const badgeWrapper = document.createElement('div');
            badgeWrapper.className = 'text-end';
            if (thread.unread_count && thread.unread_count > 0) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-danger';
                badge.textContent = thread.unread_count;
                badgeWrapper.appendChild(badge);
            }

            item.appendChild(content);
            item.appendChild(badgeWrapper);

            if (state.activeThreadId === thread.id) {
                item.classList.add('active');
            }

            item.addEventListener('click', () => selectThread(thread.id));

            listEl.appendChild(item);
        });
    };

    const renderActiveThread = (thread) => {
        state.activeThreadId = thread.id;

        headerEl.innerHTML = '';
        const title = document.createElement('div');
        title.innerHTML = `<h4 class="mb-0">${escapeHtml(thread.user?.name || `#${thread.id}`)}</h4>`;
        headerEl.appendChild(title);

        messagesEl.innerHTML = '';

        if (!thread.messages || !thread.messages.length) {
            const empty = document.createElement('p');
            empty.className = 'text-muted text-center m-0';
            empty.textContent = 'No messages yet.';
            messagesEl.appendChild(empty);
        } else {
            thread.messages.forEach((message) => appendMessageToView(message));
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        formEl.dataset.threadId = thread.id;
        textareaEl.disabled = false;
        sendBtn.disabled = false;
        textareaEl.focus();

        renderThreads();
    };

    const appendMessageToView = (message) => {
        const wrapper = document.createElement('div');
        const isAdmin = message.sender_type === 'admin';
        wrapper.className = `d-flex mb-3 ${isAdmin ? 'justify-content-end' : 'justify-content-start'}`;

        const bubble = document.createElement('div');
        bubble.className = `px-3 py-2 rounded-3 shadow-sm ${isAdmin ? 'bg-primary text-white' : 'bg-white'}`;

        const meta = document.createElement('div');
        meta.className = `small ${isAdmin ? 'text-white-50' : 'text-muted'}`;
        const senderName = message.sender?.name || (isAdmin ? (window.chatDashboardState?.authUser?.name || 'Admin') : 'User');
        meta.textContent = `${senderName} • ${formatDate(message.created_at)}`;

        const body = document.createElement('div');
        body.className = 'mt-1';
        const content = typeof message.message === 'string' ? message.message : '';
        body.innerHTML = escapeHtml(content).replace(/\n/g, '<br>');

        bubble.appendChild(meta);
        bubble.appendChild(body);
        wrapper.appendChild(bubble);

        messagesEl.appendChild(wrapper);
    };

    const updateThreadInState = (thread) => {
        const index = state.threads.findIndex((item) => item.id === thread.id);
        if (index > -1) {
            state.threads[index] = { ...state.threads[index], ...thread };
        } else {
            state.threads.unshift(thread);
        }
        state.threads.sort((a, b) => resolveThreadSortDate(b) - resolveThreadSortDate(a));
    };

    const subscribeToThread = (threadId) => {
        const echo = ensureEcho(root);
        if (!echo || state.subscriptions.has(threadId)) {
            return;
        }

        const channel = echo.private(`chat.thread.${threadId}`);
        channel.listen('ChatMessageSent', (payload) => {
            handleIncomingMessage(payload);
        });

        state.subscriptions.set(threadId, channel);
    };

    const handleIncomingMessage = (payload) => {
        if (state.activeThreadId === payload.thread_id) {
            appendMessageToView(payload);
            messagesEl.scrollTop = messagesEl.scrollHeight;
            updateThreadInState({
                id: payload.thread_id,
                last_message_at: payload.created_at,
                unread_count: 0,
            });
            renderThreads();
        } else {
            fetchThreads();
        }
    };

    const hideStartForm = () => {
        if (!startWrapper) {
            return;
        }

        startWrapper.classList.add('d-none');
        startForm?.reset();
        startForm?.setAttribute('data-selected-user', '');
        if (startHiddenInput) {
            startHiddenInput.value = '';
        }
        if (startSuggestions) {
            startSuggestions.innerHTML = '';
        }
        if (startToggleBtn) {
            startToggleBtn.disabled = false;
        }
    };

    const renderUserSuggestions = (users) => {
        if (!startSuggestions) {
            return;
        }

        startSuggestions.innerHTML = '';

        if (!users.length) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-muted small';
            empty.textContent = 'No users found.';
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
                if (startForm) {
                    startForm.setAttribute('data-selected-user', user.id);
                }
                if (startInput) {
                    startInput.value = user.email ? `${user.name} (${user.email})` : user.name;
                }
                startSuggestions.innerHTML = '';
            });
            startSuggestions.appendChild(item);
        });
    };

    const requestUserSuggestions = (term) => {
        if (!userSearchEndpoint || !startSuggestions) {
            return;
        }

        if (term.length < 2) {
            startSuggestions.innerHTML = '';
            return;
        }

        axios.get(userSearchEndpoint, { params: { search: term } })
            .then((response) => {
                const data = response.data?.data || [];
                renderUserSuggestions(data);
            });
    };

    const selectThread = (threadId) => {
        if (!threadId) {
            return;
        }

        axios.get(threadEndpointTemplate.replace('__THREAD__', threadId))
            .then((response) => {
                const data = response.data?.data || response.data;
                updateThreadInState(data);
                renderActiveThread(data);
                subscribeToThread(threadId);
            });
    };

    const fetchThreads = () => {
        axios.get(threadsEndpoint)
            .then((response) => {
                const data = response.data?.data || [];
                state.threads = data;
                state.threads.forEach((thread) => subscribeToThread(thread.id));
                renderThreads();
                if (!state.activeThreadId && state.threads.length) {
                    selectThread(state.threads[0].id);
                }
            });
    };

    formEl.addEventListener('submit', (event) => {
        event.preventDefault();
        const threadId = formEl.dataset.threadId;
        const message = textareaEl.value.trim();

        if (!threadId || !message) {
            return;
        }

        sendBtn.disabled = true;

        axios.post(messageEndpointTemplate.replace('__THREAD__', threadId), { message })
            .then((response) => {
                const data = response.data?.data || response.data;
                appendMessageToView(data);
                textareaEl.value = '';
                textareaEl.focus();
                messagesEl.scrollTop = messagesEl.scrollHeight;
                updateThreadInState({
                    id: threadId,
                    last_message_at: data.created_at,
                    unread_count: 0,
                });
                renderThreads();
            })
            .finally(() => {
                sendBtn.disabled = false;
            });
    });

    refreshBtn?.addEventListener('click', () => {
        fetchThreads();
    });

    if (startToggleBtn && startWrapper && threadCreateEndpoint) {
        startToggleBtn.addEventListener('click', () => {
            startWrapper.classList.toggle('d-none');
            if (!startWrapper.classList.contains('d-none')) {
                startInput?.focus();
            }
        });
    } else if (startToggleBtn && !threadCreateEndpoint) {
        startToggleBtn.remove();
    }

    startCancelBtn?.addEventListener('click', () => {
        hideStartForm();
    });

    startInput?.addEventListener('input', (event) => {
        if (startForm) {
            startForm.setAttribute('data-selected-user', '');
        }
        if (startHiddenInput) {
            startHiddenInput.value = '';
        }

        const term = event.target.value.trim();

        if (startSearchTimeout) {
            clearTimeout(startSearchTimeout);
        }

        startSearchTimeout = setTimeout(() => {
            requestUserSuggestions(term);
        }, 250);
    });

    startForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!threadCreateEndpoint) {
            return;
        }

        const userId = startHiddenInput?.value || startForm.getAttribute('data-selected-user');

        if (!userId) {
            startInput?.focus();
            return;
        }

        if (startSubmitBtn) {
            startSubmitBtn.disabled = true;
        }

        axios.post(threadCreateEndpoint, { user_id: userId })
            .then((response) => {
                const data = response.data?.data || response.data;
                if (!data) {
                    return;
                }
                updateThreadInState(data);
                renderThreads();
                hideStartForm();
                if (!data.last_message_at) {
                    const threadData = {
                        ...data,
                        messages: Array.isArray(data.messages) ? data.messages : [],
                    };
                    renderActiveThread(threadData);
                    subscribeToThread(data.id);
                }
                selectThread(data.id);
            })
            .finally(() => {
                if (startSubmitBtn) {
                    startSubmitBtn.disabled = false;
                }
            });
    });

    fetchThreads();
});
