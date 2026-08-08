@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <!-- Friends list -->
        <div class="col-md-4">
            <div class="card" id="friends-list">
                <div id="notify-banner" class="notify-banner" style="display:none;">
                    <span>Get notified about new messages</span>
                    <button id="notify-enable-btn" type="button">Enable</button>
                </div>
                <div class="card-header">{{ __('Friends') }}</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach ($friends as $friend)
                            <li class="list-group-item {{ (isset($otherUser) && $otherUser->id == $friend['id']) ? 'active' : '' }}" data-friend-id="{{ $friend['id'] }}">
                                <a href="{{ url('/home/'.$friend['id']) }}" class="{{ (isset($otherUser) && $otherUser->id == $friend['id']) ? 'text-white' : '' }}">
                                    <div class="avatar-wrapper">
                                        @if (!empty($friend['avatar']))
                                            <img src="{{ asset('storage/'.$friend['avatar']) }}" class="avatar-img" alt="">
                                        @else
                                            <div class="avatar-initials">{{ strtoupper(substr($friend['name'], 0, 2)) }}</div>
                                        @endif
                                        <span class="status-dot {{ $friend['is_online'] ? 'online' : 'offline' }}"></span>
                                    </div>
                                    <span class="friend-name">{{ $friend['name'] }}</span>
                                    <span class="unread-badge" style="{{ $friend['unread_count'] > 0 ? '' : 'display:none;' }}">
                                        {{ $friend['unread_count'] > 99 ? '99+' : $friend['unread_count'] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Chat window -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ $otherUser ? $otherUser->name : __('Select a friend to start chatting') }}
                </div>

                <div class="card-body" id="chat-messages" style="height: 400px; overflow-y: auto;">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @foreach ($messages as $message)
                        <div class="mb-2 {{ $message['user_id'] == Auth::id() ? 'text-end' : 'text-start' }}"
                             data-mine="{{ $message['user_id'] == Auth::id() ? '1' : '0' }}"
                             data-read="{{ $message['is_read'] }}">
                            <span class="badge {{ $message['user_id'] == Auth::id() ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $message['message'] }}
                            </span>
                            <div class="message-time">{{ \Carbon\Carbon::parse($message['created_at'])->format('g:i A') }}</div>
                        </div>
                    @endforeach
                </div>

                @if ($otherUser)
                <div class="typing-indicator" id="typing-indicator" style="display:none;">
                    <span id="typing-name"></span> is typing
                    <span class="typing-dots"><span></span><span></span><span></span></span>
                </div>
                <div class="seen-indicator" id="seen-indicator"></div>
                <div class="card-footer">
                    <form id="chat-form" class="d-flex">
                        <input type="text" id="chat-input" class="form-control me-2" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 16 16">
                                <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
                            </svg>
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var user_id = '{{ Auth::id() }}';
    var other_id = '{{ $otherUser ? $otherUser->id : "" }}';
    var group_id = '{{ isset($group_id) ? $group_id : "" }}';
    var other_name = '{{ $otherUser ? $otherUser->name : "" }}';

    var socket = io("http://localhost:3000", { query: { user_id: user_id } });

    var typingTimeout = null;

    // ---- Sending messages ----
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'chat-form') {
            e.preventDefault();

            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;

            socket.emit('chat message', {
                user_id: user_id,
                other_user_id: other_id,
                group_id: group_id,
                message: message
            });

            appendMessage(message, true);
            input.value = '';
            setSeenIndicator(false);

            socket.emit('stop_typing', { group_id: group_id, user_id: user_id, other_user_id: other_id });
            clearTimeout(typingTimeout);
        }
    });

    // ---- Typing indicator: emit while the user types ----
    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'chat-input' && group_id) {
            socket.emit('typing', { group_id: group_id, user_id: user_id, other_user_id: other_id });

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () {
                socket.emit('stop_typing', { group_id: group_id, user_id: user_id, other_user_id: other_id });
            }, 1500);
        }
    });

    socket.on('typing', function (data) {
        if (data.group_id === group_id && data.other_user_id == user_id) {
            const indicator = document.getElementById('typing-indicator');
            const nameSpan = document.getElementById('typing-name');
            if (indicator && nameSpan) {
                nameSpan.textContent = other_name;
                indicator.style.display = 'flex';
            }
        }
    });

    socket.on('stop_typing', function (data) {
        if (data.group_id === group_id && data.other_user_id == user_id) {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) indicator.style.display = 'none';
        }
    });

    // ---- Receiving messages ----
    socket.on('chat message', function (data) {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.style.display = 'none';

        if (data.group_id === group_id) {
            appendMessage(data.message, data.user_id == user_id);

            if (data.other_user_id == user_id) {
                socket.emit('mark_read', { group_id: group_id, from_id: data.user_id, to_id: user_id });

                if (document.hidden) {
                    showNotification(other_name, data.message, data.user_id);
                }
            }
        } else if (data.other_user_id == user_id) {
            incrementUnreadBadge(data.user_id);
            showNotification(getFriendName(data.user_id), data.message, data.user_id);
        }
    });

    socket.on('messages_read', function (data) {
        if (data.group_id === group_id) {
            setSeenIndicator(true);
        }
    });

    function appendMessage(text, isMine) {
        const messagesDiv = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'mb-2 ' + (isMine ? 'text-end' : 'text-start');

        const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

        div.innerHTML = '<span class="badge ' + (isMine ? 'bg-primary' : 'bg-secondary') + '">' + text + '</span>' +
                         '<div class="message-time">' + time + '</div>';

        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function setSeenIndicator(seen) {
        const indicator = document.getElementById('seen-indicator');
        if (!indicator) return;
        indicator.textContent = seen ? 'Seen' : '';
    }

    function incrementUnreadBadge(friendId) {
        const li = document.querySelector('.list-group-item[data-friend-id="' + friendId + '"]');
        if (!li) return;
        const badge = li.querySelector('.unread-badge');
        if (!badge) return;
        const current = parseInt(badge.textContent.replace('+', '')) || 0;
        const next = current + 1;
        badge.textContent = next > 99 ? '99+' : next;
        badge.style.display = 'inline-block';
    }

    function getFriendName(friendId) {
        const li = document.querySelector('.list-group-item[data-friend-id="' + friendId + '"]');
        if (!li) return 'Someone';
        const nameSpan = li.querySelector('.friend-name');
        return nameSpan ? nameSpan.textContent.trim() : 'Someone';
    }

    // ---- Browser notifications ----
    function showNotification(fromName, message, friendId) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        const notif = new Notification(fromName, {
            body: message,
            icon: '/favicon.ico'
        });

        notif.onclick = function () {
            window.focus();
            window.location.href = '/home/' + friendId;
        };
    }

    function updateNotifyBanner() {
        const banner = document.getElementById('notify-banner');
        if (!banner) return;
        if ('Notification' in window && Notification.permission === 'default') {
            banner.style.display = 'flex';
        } else {
            banner.style.display = 'none';
        }
    }

    const notifyBtn = document.getElementById('notify-enable-btn');
    if (notifyBtn) {
        notifyBtn.addEventListener('click', function () {
            Notification.requestPermission().then(function () {
                updateNotifyBanner();
            });
        });
    }

    updateNotifyBanner();

    // ---- Seen indicator on page load ----
    (function initSeenIndicator() {
        const messagesDiv = document.getElementById('chat-messages');
        if (!messagesDiv) return;
        const mineMessages = messagesDiv.querySelectorAll('[data-mine="1"]');
        if (mineMessages.length === 0) return;
        const last = mineMessages[mineMessages.length - 1];
        setSeenIndicator(last.getAttribute('data-read') === '1');
    })();
</script>
@endsection