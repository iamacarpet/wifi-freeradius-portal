<?php

require __DIR__ . "/../config.php";

foreach ($u->getUsers() as $user){
        if ( $user['lchg'] <= ( mktime()-7257600 ) ){
                echo 'Changing Password for User: ' . $user['uname'];
		$u->passwd($user['uid']);
        }
}
