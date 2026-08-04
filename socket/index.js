var express = require('express');
var app = express();
var http = require('http').Server(app);
var io = require('seocket.io')(http,{cors:{
    origin:"http://localhost:8000"
}});

var mysql = require('mysql');
var moment = require('moment');

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



http.listen(3000);