<?php if (count($patches)) { ?>
<div class="explain">
<table>
 <tr>
  <td>
   <a href="bug.php?id=<?php echo $bug_id; ?>">Return to Bug #<?php echo clean($bug_id) ?></a>
   <?php if ($canpatch) { ?>
        | <a href="patch-add.php?bug_id=<?php echo $bug_id; ?>">Add a Patch</a>
   <?php } ?>
  </td>
 </tr>
<?php foreach ($patches as $patch_name => $revs) { ?>
 <tr>
  <th class="details">
  		Patch <a href="?bug_id=<?php echo $bug_id; ?>&amp;patch=<?php echo urlencode($patch_name); ?>"><?php echo clean($patch_name); ?></a>
  </th>
  <td>
   <?php foreach ($revs as $rev) { ?>
        revision <a href="patch-display.php?bug_id=<?php echo $bug_id;?>&amp;patch=<?php echo urlencode($patch_name); ?>&amp;revision=<?php echo $rev[0]; ?>&amp;display=1"><?php echo format_date($rev[0]); ?></a>
        by <a href="/user/<?php echo $rev[1] ?>"><?php echo $rev[1]; ?></a><br />
   <?php } //foreach ($revs as $rev) ?>
  </td>
 </tr>
<?php } ?>
</table>
</div>
<?php } ?>
