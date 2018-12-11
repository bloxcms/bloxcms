<?
    
/**
search "password", then case iinsensitive "password"

сделать поле password varchar(255)


//https://github.com/ircmaxell/password_compat
*/



require ...'libraries/PasswordCompat/password.php';


# 1.
$hash = password_hash($password, PASSWORD_DEFAULT);
//It produces a 60 character hash as the result.

# 2.
if (password_verify($password, $hash)) {
        /* Valid */
    } else {
        /* Invalid */
    }