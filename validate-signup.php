<?php

require("config.php");
    
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if(!isset($_POST["FirstName"]) || !isset($_POST["LastName"]) || !isset($_POST["uEmail"]) || !isset($_POST["uPassword"]))
{
    servResponse("One or more fields empty!");
    header("Location: index.php");
    die();
}

//Retrieve submitted data
$FirstName = $_POST["FirstName"];
$LastName = $_POST["LastName"];
$uEmail = $_POST["uEmail"];
$uPassword = $_POST["uPassword"];

//REVALIDATING user input SERVERSIDE

$emailRegEx = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

if(!preg_match($emailRegEx, $uEmail)){
    servResponse("Invalid Email!");
    header("refresh:3;url=index.php");
    die();
}

$passwordRegEx = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{9,})/";
if(!preg_match($passwordRegEx, $uPassword)){
    servResponse("Password needs to contain: Uppercase, Lowercase, Special as well as Numeric characters and be more than 8 characters long.");
    header("refresh:3;url=index.php");
    die();
}

//Hasing the password, generating dynamic salt and storing it
$uSalt = generateSalt();

$options = array(
    'salt' => $uSalt
);
//PASSWORD_DEFAULT ensures the latest, most secure algorithm is used (currently PASSWORD_BCRYPT)
$password_hash = password_hash($uPassword, PASSWORD_DEFAULT, $options);

//Generate APIKey
$uAPIKey = bin2hex(openssl_random_pseudo_bytes(32));

//Checking for Duplicate user
$sql = "SELECT * FROM Users WHERE uEmail='$uEmail'";
$result = $conn->query($sql); 
    
if ($result->num_rows > 0)
{
    servResponse('Email already exists in database! <br/> <button onclick="location.href=\'login.php\';" value="Log in" class="mediumspacing registerbtn smallbtn">Log in</button>');
} else {

//Insert User into DB
$sql = "INSERT INTO Users (FirstName, LastName, uEmail, uPassword, uSalt, uAPIKey)
VALUES ('$FirstName', '$LastName', '$uEmail', '$password_hash', '$uSalt', '$uAPIKey')";

//Success
if ($conn->query($sql) === TRUE) {
    servResponse('Welcome to CluedUP, '.$FirstName.'! Your account has been registered. 
    <div class="smalltext mediumspacing">Your new API key: '.$uAPIKey.' </div>
    <button onclick="location.href=\'login.php\';" value="Log in" class="mediumspacing registerbtn smallbtn">Log in</button>');
} else {
    servResponse("Error: " . $sql . "<br>" . $conn->error);
}
}

$conn->close();



function generateSalt() {
     $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/\\][{}\;:?.>,<!@#$%^&*()-_=+|';
     $randStringLen = 64;

     $randString = "";
     for ($i = 0; $i < $randStringLen; $i++) {
         $randString .= $characters[mt_rand(0, strlen($characters) - 1)];
     }

     return $randString;
} 

function servResponse($msg){
    echo "<!DOCTYPE html>
<html lang='en'>
   <head>
      <meta charset='UTF-8'>
      <title>CluedUP - The Premiere Movie Database</title>
      <meta name='author' content='Giovanni Joubert'>
      <meta name='description' content='The Premiere Movie Database'>
      <link rel='stylesheet' href='css/main.css' type='text/css'>
      <link rel='icon' href='img/favico.png' type='image/png'>
      <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.7.1/css/all.css' integrity='sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr' crossorigin='anonymous'>
      <meta content='width=device-width, initial-scale=1' name='viewport' />
      <script src='js/cluedup.js'></script>
      <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
   </head>
   <body>
      <header>
         <nav>
            <span><img alt='CluedUP Logo' class='topLogo centerLogo' src='img/mainlogo.svg'></span>
            <ul class='topNav servResponseNav'><li><a href='index.php'><i class='fa fa-home'></i>Home</a></li></ul>
         </nav>
         <div class='welcome-msg'>
            <h1>Welcome</h1>
            <h3>to The Premiere Movie Database</h3>
         </div>
         <svg class='custom-header-shape' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' preserveAspectRatio='none'>
            <polygon fill='white' points='0,100 100,0 100,100'/>
         </svg>
      </header>
      <h2 class='servResponse'> $msg </h2>
      <footer>
         <p>COS216 Practical 1 - u18009035</p>
      </footer>
   </body>
</html>";
}

?>