<div class="explain">
<table>
 <tr>
  <td>
   <a href="bug.php?id=<?php echo $bug_id; ?>">Return to Bug #<?php echo $bug_id; ?></a>
   <?php if ($canpatch) { ?> | <a href="patch-add.php?bug_id=<?php echo $bug_id; ?>">Add a Patch</a><?php } ?>
  </td>
 </tr>
<?php if (!count($patches)) { ?>
 <tr>
  <th class="form-label_left">
   No patches
  </th>
 </tr>
<?php } else {
    foreach ($patches as $patch_name => $revisions) {
?>
 <tr>
  <th class="details">
   Patch <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&patchname=<?php echo urlencode($patch_name); ?>"><?php echo clean($patch_name); ?></a>
  </th>
  <td>
   <?php foreach ($revisions as $revision) { ?>
   revision <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&patchname=<?php echo urlencode($patch_name)
      ?>&revision=<?php echo $revision[0] ?>&display=1"><?php echo format_date($revision[0]) ?></a> by <a href="/user/<?php echo $revision[1] ?>"><?php echo $revision[1] ?></a><br />
   <?php } ?>
  </td>
 </tr>
<?php
    } 
} ?>
</table>
</div>
