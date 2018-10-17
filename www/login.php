<?php

session_start();

require_once '../include/prepend.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

response_header('Login');

if (isset($_POST['user'])) {
  $referer = $_POST['referer'];

  bugs_authenticate($user, $pwd, $logged_in, $user_flags);

  if ($logged_in === 'developer') {
	if (!empty($_POST['referer']) &&
		preg_match("/^{$site_method}:\/\/". preg_quote($site_url) .'/i', $referer) &&
		parse_url($referer, PHP_URL_PATH) != '/logout.php') {
		redirect($referer);
	}
    redirect('index.php');
  } else {
?>
    <div style="background: #AB1616; padding: 3px; width: 300px; color: #FFF; margin: 3px;">Wrong username or password!</div>
<?php
  }
} else {
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
}

?>

<form method="post" action="login.php">
<input type="hidden" name="referer" value="<?php print htmlspecialchars($referer); ?>">
<table>
 <tr>
  <th align="right">Username:</th>
  <td><input type="text" name="user" value="<?php print isset($user) ? htmlspecialchars($user) : ''; ?>">@php.net
 </tr>
 <tr>
  <th align="right">Password:</th>
  <td><input type="password" name="pw" value="<?php print isset($pwd) ? htmlspecialchars($pwd) : ''; ?>">
 </tr>
 <tr>
  <td align="center" colspan="2"><input type="submit" value="Login"></td>
 </tr>
</table>
</form>

<?php
response_footer();
?>
