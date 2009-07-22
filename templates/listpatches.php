<?php if (count($patches)): ?>
<div class="explain">
<table>
 <tr>
  <td>
   <a href="bug.php?id=<?php echo urlencode($bug) ?>">Return to Bug #<?php echo clean($bug) ?></a>
   <?php if ($canpatch): ?> | <a href="patch-add.php?bug_id=<?php echo urlencode($bug) ?>">Add a Patch</a>
   <?php endif; //if ($canpatch) ?>
  </td>
 </tr>
<?php
    foreach ($patches as $patch_name => $revs):
?>
 <tr>
  <th class="details">
   Patch <a href="?bug_id=<?php echo urlencode($bug) ?>&amp;patch=<?php echo urlencode($patch_name)
      ?>"><?php echo clean($patch_name); ?></a>
  </th>
  <td>
   <?php foreach ($revs as $rev) { ?>
   revision <a href="patch-display.php?bug_id=<?php echo urlencode($bug) ?>&amp;patch=<?php echo urlencode($patch_name)
      ?>&amp;revision=<?php echo $rev[0] ?>&amp;display=1"><?php echo format_date($rev[0]) ?></a> by <a href="/user/<?php echo $rev[1] ?>"><?php echo $rev[1] ?></a><br />
   <?php } //foreach ($revs as $rev) ?>
  </td>
 </tr>
<?php
    endforeach; //foreach ($patches as $patch_name => $revs)
?>
</table>
</div>
<?php endif; //if (count($patches)) ?>
