<?php

$user = trim(@$_POST['txtuser']);
$pass = trim(@$_POST['txtpass']);

$error_id = 0;

define('LOGIN_LNG', 1);

$login_txt = login_txt('../_txt/'.LOGIN_LNG.'/login.txt');


if ($user && $pass) {

    if (!preg_match("/^[.a-zA-Z0-9]+$/",$user)) {
        $error_id = 1;
    }

    if (strlen($user)>=30 || strlen($user)<=5) {
        $error_id = 1;
    }

    if ($error_id) {

        $error_msg = $login_txt['uname_error'];
        $user = '';

    } else {

        // LOGIN
        require '../__ssn/ssn_boot.php';
    }
}



function login_txt($path) {

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $x_key  = strstr($line, ' ', true);             // name: the part before the needle (spacer)
        $x_value = trim(strstr($line, ' ', false));     // value: the part after the needle (spacer)

        $r[$x_key] = $x_value;
    }

    return $r;
}



?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Владимир Благојевић">

    <title>ProDesk Login</title>

    <link href="/_pkg/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body {
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #eee;
            font-family: verdana, sans-serif;
        }

        .form-signin {
            max-width: 330px;
            padding: 15px;
            margin: 0 auto;
        }
        .form-signin .form-signin-heading {
            margin-bottom: 10px;
            background-color: #ddd;
            padding: 7px 0;
            font-weight: bold;
            font-variant: small-caps;
            color: #aaa;
            border-radius: 5px;
        }
        .form-signin .form-control {
            position: relative;
            height: auto;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            padding: 10px;
            font-size: 16px;
        }
        .form-signin .form-control:focus {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

    </style>

</head>

<body>

<div class="container">

<?php

    if (isset($error_msg)) {
        echo '<div class="alert alert-danger" role="alert">'.$error_msg.'</div>';
    }

?>
    <form action="" method="post" class="form-signin" autocomplete="off">
        <h2 class="form-signin-heading text-center"><?=$login_txt['prodesk']?></h2>

        <label for="txtuser" class="sr-only"><?=$login_txt['uname']?></label>
        <input type="text" name="txtuser" id="txtuser" class="form-control" placeholder="<?=$login_txt['uname']?>" required autofocus>

        <label for="txtpass" class="sr-only"><?=$login_txt['pass']?></label>
        <input type="password" name="txtpass" id="txtpass" class="form-control" placeholder="<?=$login_txt['pass']?>" required>

        <button class="btn btn-lg btn-primary btn-block" type="submit"><?=$login_txt['submit']?></button>
    </form>

</div>

</body>
</html>
