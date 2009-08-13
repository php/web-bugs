<?php
response_header('Add Patch :: ' . clean($package_name));
?>
<h2>Add a Patch to <a href="<?php echo $bug_id; ?>">Bug #<?php echo $bug_id; ?></a></h2>
<ul>
 <li>One problem per patch, please</li>
 <li>Patches must be 100k or smaller</li>
 <li>Only text/plain files accepted</li>
 <li>choose a meaningful patch name (i.e. add-fronk-support)</li>
</ul>
<form name="patchform" method="post" action="patch-add.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="102400" />
<input type="hidden" name="bug" value="<?php echo $bug_id; ?>" />
<?php
if ($errors) {
    foreach ($errors as $err) {
        echo '<div class="errors">' . htmlspecialchars($err) . '</div>';
    }
}
?>
<table>
<?php
if (!$loggedin) {?>
 <tr>
  <th class="form-label_left">
   Email Address (MUST BE VALID)
  </th>
  <td class="form-input">
   <input type="text" name="email" value="<?php echo clean($email); ?>" />
  </td>
 </tr>
 <tr>
  <th>Solve the problem : <?php echo $captcha; ?> = ?</th>
  <td class="form-input"><input type="text" name="captcha" /></td>
 </tr>
<?php } ?>
 <tr>
  <th class="form-label_left">
   Patch Name
  </th>
  <td class="form-input">
   <input type="text" maxlength="80" size="40" name="name" value="<?php echo clean($name); ?>" /><br />
   <small>The patch name must be shorter than 80 characters and it must only contain alpha-numeric characters, dots, underscores or hyphens.</small>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Patch File
  </th>
  <td class="form-input">
   <input type="file" name="patch"/>
  </td>
 </tr>
<?php if (!empty($patches)) { ?>
 <tr>
  <th class="form-label_left">
   Old patches this patch should replace:
  </th>
  <td class="form-input">
   <select name="obsoleted[]" multiple="true" size="5">
    <option value="0">(none)</option>
<?php
   foreach ($patches as $patchname => $patch2) {
       foreach ($patch2 as $patch) {
           echo '<option value="', htmlspecialchars($patchname . '#' . $patch[0]),
                '">', htmlspecialchars($patchname), ', Revision ',
                format_date($patch[0]), ' (', $patch[1], ')</option>';
       }
   }
?>
   </select>
  </td>
 </tr>
<?php } ?>
</table>
<br />
<input type="submit" name="addpatch" value="Save" />
</form>
<?php if (!empty($patches)) { ?>
<h2>Existing patches:</h2>
<?php
}

$canpatch = false;
require "{$ROOT_DIR}/templates/listpatches.php";
response_footer();
