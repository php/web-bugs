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
  <td>
   No patches
  </td>
 </tr>
<?php } else {
    foreach ($patches as $patch_name => $revisions) {
    	$url_patch_name = urlencode($patch_name);
?>
 <tr>
  <th class="details">
   Patch <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&amp;patchname=<?php echo $url_patch_name; ?>"><?php echo htmlspecialchars($patch_name); ?></a>
  </th>
  <td>
   <?php foreach ($revisions as $revision) { ?>
   revision <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&amp;patchname=<?php echo $url_patch_name; ?>&amp;revision=<?php echo $revision[0] ?>&amp;display=1"><?php echo format_date($revision[0]) ?></a> by <a href="/user/<?php echo $revision[1] ?>"><?php echo $revision[1] ?></a><br />
   <?php } ?>
  </td>
 </tr>
<?php
    } 
} ?>
</table>
</div>
