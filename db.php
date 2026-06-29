<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "turbofuel"
);

if(!$conn){
    die("Connection Failed: " . mysqli_connect_error());
}

?>