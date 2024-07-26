<?php 

$dbServername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "migration";

$connect = new mysqli($dbServername, $dbUsername, $dbPassword, $dbName);


/*
// Check connection
if ($connect->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}*/
?>