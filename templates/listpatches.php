<?php if (count($patches)) { ?>
<div class="explain">
<table>
 <tr>
  <td>
   <a href="bug.php?id=<?php echo $bug_id; ?>">Return to Bug #<?php echo $bug_id; ?></a>
   <?php if ($canpatch) { ?>
        | <a href="patch-add.php?bug_id=<?php echo $bug_id; ?>">Add a Patch</a>
   <?php } ?>
  </td>
 </tr>
<?php
foreach ($patches as $fpa) { $fixed[$fpa['patch']][] = [ $fpa['revision'], $fpa['developer'] ]; }
foreach ($fixed as $pname => $revs) { ?>
 <tr>
  <th class="details">
   Patch <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&amp;patch=<?php echo urlencode($pname); ?>&amp;revision=latest"><?php echo clean($pname); ?></a>
  </th>
  <td>
   <?php foreach ($revs as $rev) { ?>
        revision <a href="patch-display.php?bug_id=<?php echo $bug_id;?>&amp;patch=<?php echo urlencode($pname); ?>&amp;revision=<?php echo $rev[0]; ?>&amp;display=1"><?php echo format_date($rev[0]); ?></a>
        by <?php echo spam_protect($rev[1]); ?></a><br>
   <?php } //foreach ($revs as $rev); ?>
  </td>
 </tr>
<?php } ?>
</table>
</div>
<?php } ?>
