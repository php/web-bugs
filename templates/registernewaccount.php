<?php
if (count($errors)) {
    echo '<div class="warnings">';
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . "<br />";
    }
    echo '</div>';
}
?>
<form name="confirmaccount" onsubmit="javascript:doMD5(document.forms['confirmaccount'])" method="POST" <?php if (!DEVBOX)  { ?>action="https://pear.php.net/account-request-confirm.php" <?php } ?>>
<input type="hidden" name="salt" value="<?php echo htmlspecialchars($salt) ?>" />
<script type="text/javascript" src="/javascript/md5.js"></script>
<script type="text/javascript">
function doMD5(frm) {
    frm.PEAR_PW.value = hex_md5(frm.PEAR_PW.value);
    frm.PEAR_PW2.value = hex_md5(frm.PEAR_PW2.value);
    frm.isMD5.value = 1;
}
</script>
<input type="hidden" name="isMD5" value="0" />
<table class="form-holder" cellspacing="1">
 <tr>
  <th class="form-label_left">
Choose a Use<span class="accesskey">r</span>name:</th>
  <td class="form-input">
<input size="20" name="PEAR_USER" accesskey="r" value="<?php echo htmlspecialchars($user) ?>" /> (only letters [lowercase], digits, and underscore)</td>
 </tr>
 <tr>
  <th class="form-label_left">Choose a Password:</th>
  <td class="form-input">
<input size="20" name="PEAR_PW" type="password" /></td>
 </tr>
 <tr>
  <th class="form-label_left">Confirm Password:</th>
  <td class="form-input">
<input size="20" name="PEAR_PW2" type="password" /></td>
 </tr>
 <tr>
  <th class="form-label_left">Your email:</th>
  <td class="form-input"><?php echo htmlspecialchars($email) ?></td>
 </tr>
 <tr>
  <th class="form-label_left">Your Name:</th>
  <td class="form-input"><input size="20" maxlength="100" name="name" type="text" value="<?php
    echo htmlspecialchars($name) ?>" /></td>
 </tr>
</table>
<input type="submit" name="confirmdetails" value="Confirm Account" />
</form>