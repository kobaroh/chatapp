var express = require('express');
var app = express();
var http = require('http').Server(app);
var io = require('socket.io')(http, {
    cors: {
        origin: "http://localhost:8000"
    }
});

var mysql = require('mysql2');
var moment = require('moment');

var con = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'chatapp'
});

con.connect(function (err) {
    if (err) throw err;
    console.log("Database Connected");
});

var sockets = {};

io.on('connection', function (socket) {
    var user_id = socket.handshake.query.user_id;

    if (!sockets[user_id]) {
        sockets[user_id] = [];
    }
    sockets[user_id].push(socket);

    socket.broadcast.emit('user_connected', user_id);

    con.query('UPDATE users SET is_online = 1 WHERE id = ?', [user_id], function (err) {
        if (err) {
            console.error('is_online update error:', err.message);
            return;
        }
        console.log("User Connected", user_id);
    });

    socket.on('chat message', function (data) {
        var group_id = data.group_id;
        var from_id = data.user_id;
        var to_id = data.other_user_id;
        var message = data.message;

        con.query(
            'INSERT INTO chats (user_id, other_user_id, message, group_id, is_read, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())',
            [from_id, to_id, message, group_id],
            function (err) {
                if (err) {
                    console.error('chat message insert error:', err.message);
                    return;
                }

                if (sockets[to_id]) {
                    sockets[to_id].forEach(function (recipientSocket) {
                        recipientSocket.emit('chat message', data);
                    });
                }

                if (sockets[from_id]) {
                    sockets[from_id].forEach(function (senderSocket) {
                        if (senderSocket.id !== socket.id) {
                            senderSocket.emit('chat message', data);
                        }
                    });
                }
            }
        );
    });

    socket.on('typing', function (data) {
        if (sockets[data.other_user_id]) {
            sockets[data.other_user_id].forEach(function (s) {
                s.emit('typing', data);
            });
        }
    });

    socket.on('stop_typing', function (data) {
        if (sockets[data.other_user_id]) {
            sockets[data.other_user_id].forEach(function (s) {
                s.emit('stop_typing', data);
            });
        }
    });

    socket.on('mark_read', function (data) {
        con.query(
            'UPDATE chats SET is_read = 1 WHERE group_id = ? AND user_id = ? AND other_user_id = ? AND is_read = 0',
            [data.group_id, data.from_id, data.to_id],
            function (err) {
                if (err) {
                    console.error('mark_read error:', err.message);
                    return;
                }
                if (sockets[data.from_id]) {
                    sockets[data.from_id].forEach(function (s) {
                        s.emit('messages_read', { group_id: data.group_id, by: data.to_id });
                    });
                }
            }
        );
    });

    socket.on('disconnect', function () {
        socket.broadcast.emit('user_disconnected', user_id);

        for (var index in sockets[user_id]) {
            if (socket.id == sockets[user_id][index].id) {
                sockets[user_id].splice(index, 1);
            }
        }

        con.query('UPDATE users SET is_online = 0 WHERE id = ?', [user_id], function (err) {
            if (err) {
                console.error('is_online disconnect update error:', err.message);
                return;
            }
            console.log("User Disconnected", user_id);
        });
    });
});

http.listen(3000, function () {
    console.log("Socket server running on port 3000");
});