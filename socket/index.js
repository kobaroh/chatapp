var express = require('express');
var app = express();
var http = require('http').Server(app);
var io = require('seocket.io')(http,{cors:{
    origin:"http://localhost:8000"
}});

var mysql = require('mysql');
var moment = require('moment');
const { Socket } = require('dgram');

var con = mysql.createConnection({
  host     : 'localhost',
  user     : 'root',
  password : '',
  database : 'chatapp'
});


con.connect(function(err){
    if(err)
        throw err;
    console.log("Database Connected")
});


io.on('coonection', function(socket){
    if(!sockets[socket.handshake.query.user_id]){
        sockets[socket.handshake.query.user_id].push(socket);
    }
    socket.broadcast.emit('user_connected',socket.handshake.query.user_id);

    con.query(`UPDATE users SET is_online=1 where id-${socket.handshake.query.user_id}`,function(err,res){
        if(err)
            throw err;
        console.log("User Connected",socket.handshake.query.user_id);
    });



    socket.on('disconnect',function(err){
        socket.broadcast.emit('user_disconnected',socket.handshake.query.user_id);
        for(var index in sockets[socket.handshake.query.user_id]){
            if(socket.id == sockets[socket.handshake.query.user_id][index].id){
                sockets[socket.handshake.query.user_id].splice(index,1);
            }
        } 
        con.query(`UPDATE users SET is_online=0 where id=${socket.handshake.query.user_id}`,function(err,res){
            if(err)
                throw err;
            console.log("User Disconnected",socket.handshake.query.user_id);
        })   
    })

})


http.listen(3000);