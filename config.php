<?php
$api = "bot";
$api .="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"; //Replace with your bot's token.

//Connection to Database.
define(HOSTDB,"localhost");
define(USERDB,"user");
define(PASSWORDDB,"password");
define(DATABASE,"my_database");
$dbuser = new mysqli(HOSTDB, USERDB, PASSWORDDB, DATABASEDB);

//Other.
$langdir = "lang/";
$owner = 123456; //Replace with your Telegram ID (@userinfobot). The feedbacks will be sent to this user.
?>
