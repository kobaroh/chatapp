@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <!-- Friends list -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">{{ __('Friends') }}</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach ($friends as $friend)
    <li class="list-group-item {{ (isset($otherUser) && $otherUser->id == $friend['id']) ? 'active' : '' }}">
        <a href="{{ url('/home/'.$friend['id']) }}" class="{{ (isset($otherUser) && $otherUser->id == $friend['id']) ? 'text-white' : '' }}">
            <div class="avatar-wrapper">
                @if (!empty($friend['avatar']))
                    <img src="{{ asset('storage/'.$friend['avatar']) }}" class="avatar-img" alt="">
                @else
                    <div class="avatar-initials">{{ strtoupper(substr($friend['name'], 0, 2)) }}</div>
                @endif
                <span class="status-dot {{ $friend['is_online'] ? 'online' : 'offline' }}"></span>
            </div>
            {{ $friend['name'] }}
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
                        <div class="mb-2 {{ $message['user_id'] == Auth::id() ? 'text-end' : 'text-start' }}">
                            <span class="badge {{ $message['user_id'] == Auth::id() ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $message['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @if ($otherUser)
                <div class="card-footer">
                    <form id="chat-form" class="d-flex">
                        <input type="text" id="chat-input" class="form-control me-2" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="btn btn-primary">Send</button>
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
    console.log('SCRIPT START');

    var user_id = '{{ Auth::id() }}';
    var other_id = '{{ $otherUser ? $otherUser->id : "" }}';
    var group_id = '{{ isset($group_id) ? $group_id : "" }}';

    console.log('VARS SET', user_id, other_id, group_id);

    var socket = io("http://localhost:3000", { query: { user_id: user_id } });

    console.log('SOCKET CREATED');

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
        }
    });

    console.log('LISTENER ATTACHED');

    socket.on('chat message', function (data) {
        if (data.group_id === group_id) {
            appendMessage(data.message, data.user_id == user_id);
        }
    });

    function appendMessage(text, isMine) {
        const messagesDiv = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'mb-2 ' + (isMine ? 'text-end' : 'text-start');
        div.innerHTML = '<span class="badge ' + (isMine ? 'bg-primary' : 'bg-secondary') + '">' + text + '</span>';
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    console.log('SCRIPT END');
</script>
@endsection