<?php response_header('Add Patch :: ' . htmlspecialchars($package)); ?>
<h2>Add a Patch to <a href="bug.php?id=<? echo $bug_id; ?>">Bug #<?php echo $bug_id; ?></a>
<?php if ($site != 'php') { ?>
 for Package <?php echo '<a href="/package/', htmlspecialchars($package), '">', htmlspecialchars($package), '</a>'; ?>
<?php } ?>
</h2>
<ul>
 <li>One problem per patch, please</li>
 <li>Patches must be 20k or smaller</li>
 <li>Only text/plain files accepted</li>
 <li>choose a meaningful patch name (i.e. add-fronk-support)</li>
</ul>
<form name="patchform" method="post" action="patch-add.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="20480" />
<input type="hidden" name="bug_id" value="<?php echo $bug_id; ?>" />
<?php
if ($errors) {
    foreach ($errors as $err) {
        echo '<div class="errors">' . htmlspecialchars($err) . '</div>';
    }
}
?>
<table>
<?php if (!$loggedin) { ?>
 <tr>
  <th class="form-label_left">
   Email Address (MUST BE VALID)
  </th>
  <td class="form-input">
   <input type="text" name="email" value="<?php echo htmlspecialchars($email) ?>" />
  </td>
 </tr>
 <tr>
  <th>Solve the problem : <?php echo $captcha; ?> = ?</th>
  <td class="form-input"><input type="text" name="captcha" /></td>
 </tr>
<?php } ?>
 <tr>
  <th class="form-label_left">
   Choose an existing Patch to update, or add a new one
  </th>
  <td class="form-input">
   <input type="text" maxlength="40" name="patchname" value="<?php echo htmlspecialchars($patchname); ?>" /><br />
   <small>The patch name must be shorter than 40 characters and it must only contain alpha-numeric characters, dots, underscores or hyphens.</small>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Patch File
  </th>
  <td class="form-input">
   <input type="file" name="patchfile" />
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Old patches this patch should replace:
  </th>
  <td class="form-input">
   <select name="obsoleted[]" multiple="true" size="5">
    <option value="0">(none)</option>
   <?php
   foreach ($patches as $patch_name => $patch2) {
       foreach ($patch2 as $patch) {
           echo '<option value="', htmlspecialchars($patch_name . '#' . $patch[0]),
                '">', htmlspecialchars($patch_name), ', Revision ',
                format_date($patch[0]), ' (', $patch[1], ')</option>';
       }
   }
   ?>
   </select>
  </td>
 </tr>
</table>
<input type="submit" name="addpatch" value="Save" />
</form>
<h2>Existing patches:</h2>

<?php
$canpatch = false;
require $templates_path . '/templates/listpatches.php';
response_footer();
