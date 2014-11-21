<?php

require __DIR__ . "/config.php";

switch ($_GET['act']){
	case 'passwd':
		if (!empty($_GET['id'])){
			$u->passwd($_GET['id']);
			reload($_GET['id']);
		}
		break;
	case 'uadd':
		if (!empty($_GET['name']) && !empty($_GET['email'])){
			$uid = $u->add($_GET['name'], $_GET['email']);
			reload($uid);
		}
		break;
	case 'udel':
		if (!empty($_GET['id'])){
			$u->del($_GET['id']);
			reload("");
		}
		break;
	case 'devadd':
		if (!empty($_GET['id']) && !empty($_GET['macaddr']) && !empty($_GET['desc'])){
			$d->add($_GET['id'], str_replace(':', '-', strtolower($_GET['macaddr'])), $_GET['desc']);
			reload($_GET['id']);
		}
		break;
	case 'ddel':
		if (!empty($_GET['id'])){
			$uid = $d->uidByID($_GET['id']);
			$d->del($_GET['id']);
			reload($uid);
		}
		break;
	default:
		break;
}

function reload($uid){
	if ($uid != ""){
		header("Location: ./?curr=uid" . $uid . "#uid" . $uid);
	} else {
		 header("Location: ./");
	}
	die();
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>WiFi Management Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/m-styles.min.css" rel="stylesheet">
    <link href="css/m-forms.min.css" rel="stylesheet">
    <link href="css/m-buttons.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet"> 
    
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300' rel='stylesheet' type='text/css'>

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    <script type="text/javascript">
	<!--
	    function toggle_visibility(id) {
	       var e = document.getElementById(id);
	       if(e.style.display == '')
	          e.style.display = 'none';
	       else
	          e.style.display = '';
	    }
	//-->
	</script>

  </head>

  <body>
	<div align="center">
    	<h1>WiFi Users</h1>
    	<br />
    	
    	<div style="width: 95%"><table class="table table-bordered">
        	<thead>
            	<tr>
            		<th>USERNAME</th>
                	<th>EMAIL ADDRESS</th>
                	<th>PASSWORD LAST CHANGED</th>
                	<th>DEVICES</th>
                    <th>CONTROLS</th>
            	</tr>
        	</thead>
            <tbody>
            	<?php
				foreach( $u->getUsers() as $user ){
				?>
            	<tr>
                	<td style="vertical-align:middle"><a name="uid<?= $user['uid'] ?>"><?= $user['uname'] ?></a></td>
                    <td style="vertical-align:middle"><?= $user['email'] ?></td>
                    <td style="vertical-align:middle"><?= date('d\<\s\u\p\>S\<\/\s\u\p\> F Y', $user['lchg']) ?> (<a href="#uid<?= $user['uid'] ?>" onClick="toggle_visibility('uid<?= $user['uid'] ?>pw');">View</a>)</td>
                    <td style="vertical-align:middle"><a href="#uid<?= $user['uid'] ?>" onClick="toggle_visibility('uid<?= $user['uid'] ?>dev');">View Devices</a></td>
                    <td style="vertical-align:middle"><a href="?act=udel&id=<?= $user['uid'] ?>" style="vertical-align: text-top;" class="m-btn mini red">REMOVE</a></td>
           		</tr>
                <tr id="uid<?= $user['uid'] ?>pw" <?php if (@$_GET['curr'] != "uid" . $user['uid']) { echo "style=\"display: none;\""; } ?>>
                	<td>&nbsp;</td>
                    <td colspan="4" class="warning"><b>Current Password</b>: <?= $user['passwd'] ?><br /><b><a href="?act=passwd&id=<?= $user['uid'] ?>" style="vertical-align: text-top;" class="m-btn mini blue">CHANGE NOW</a> - Last Changed</b>: <?= date('h:i:s A F d\<\s\u\p\>S\<\/\s\u\p\>, Y', $user['lchg']) ?></td>
               	</tr>
                <tr id="uid<?= $user['uid'] ?>dev" <?php if (@$_GET['curr'] != "uid" . $user['uid']) { echo "style=\"display: none;\""; } ?>>
                	<td>&nbsp;</td>
                    <td colspan="4" class="info"><b>Devices for user "<?= $user['uname'] ?>"</b>:<br /><br /><?php
                    foreach ( $d->getDevices($user['uid']) as $device ){
						?><a href="?act=ddel&id=<?= $device['did'] ?>" style="vertical-align: text-top;" class="m-btn mini red">REMOVE</a>&nbsp;<?= $device['des'] ?>, <?= $device['mac'] ?><br /><?php
					}?><br /><form style="margin: 0px; padding: 0px;" action="#" method="get"><input type="hidden" name="act" value="devadd" /><input type="hidden" name="id" value="<?= $user['uid'] ?>" /><input type="text" value="" class="input-text m-wrap m-ctrl-large" id="name" name="macaddr" placeholder="MAC ADDRESS - e.g. f8-db-7f-72-05-e0"/>&nbsp;&nbsp;<input type="text" value="" class="input-text m-wrap m-ctrl-large" id="email" name="desc"  placeholder="DESCRIPTION" />&nbsp;&nbsp;<input type="submit" class="m-btn green" value="Add Device" /></form></td>
               	</tr>
                <?php
				}
				?>
           	</tbody>
        </table></div>
        
        <h2>Add User</h2>
        <form style="margin: 0px; padding: 0px;" method="get" action="#">
			<fieldset>
            	<input type="text" value="" class="input-text m-wrap m-ctrl-large" id="name" name="name" placeholder="USERNAME"/>&nbsp;&nbsp;
                <input type="text" value="" class="input-text m-wrap m-ctrl-large" id="email" name="email"  placeholder="EMAIL ADDRESS" />&nbsp;&nbsp;
                <input type="hidden" name="act" value="uadd" />
                <input type="submit" class="m-btn green" value="Add User" /></div>
           </fieldset>
       </form>
    </div>
  </body>

  <script src="js/jquery.js"></script>
  <script src="js/jquery.form.js"></script>   
  <script src="js/bootstrap.js"></script>

</html>
