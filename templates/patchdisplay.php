<?php

response_header('Patch :: ' . htmlspecialchars($package) . " :: Bug #{$bug_id}"); 

?>
<h1>Patch version <?php echo format_date($revision) ?> for <?php echo htmlspecialchars($package) ?> Bug #<?php echo $bug_id; ?></h1>
<a href="bug.php?id=<?php echo $bug_id; ?>">Return to Bug #<?php echo $bug_id; ?></a> 
| <a href="patch-display.php?bug_id=<?php echo $bug_id; ?>&patchname=<?php echo urlencode($patchname)
    ?>&revision=<?php echo urlencode($revision) ?>&download=1">Download this patch</a><br />
<?php
if (count($obsoletedby)) {
    echo '<div class="warnings">This patch is obsolete</div><p>Obsoleted by patches:<ul>';
    foreach ($obsoletedby as $betterpatch) {
        echo '<li><a href="patch-display.php?patchname=',
             urlencode($betterpatch['patch']),
             '&bug_id=', $bug_id, '&revision=', $betterpatch['revision'],
             '">', htmlspecialchars($betterpatch['patch']), ', revision ',
             format_date($betterpatch['revision']), '</a></li>';
    }
    echo '</ul></p>';
}
if (count($obsoletes)) {
    echo '<div class="warnings">This patch renders other patches obsolete</div>',
         '<p>Obsolete patches:<ul>';
    foreach ($obsoletes as $betterpatch) {
        echo '<li><a href="patch-display.php?patchname=',
             urlencode($betterpatch['obsolete_patch']),
             '&bug_id=', $bug_id,
             '&revision=', $betterpatch['obsolete_revision'],
             '">', htmlspecialchars($betterpatch['obsolete_patch']), ', revision ',
             format_date($betterpatch['obsolete_revision']), '</a></li>';
    }
    echo '</ul></p>';
}
?>
Patch Revisions:
<?php
echo '<ul>';
foreach ($revisions as $i => $rev) {
    echo '<li><a href="patch-display.php?bug_id=', $bug_id, '&patchname=',
         urlencode($patchname), '&revision=', urlencode($rev[0]), '">',
         format_date($rev[0]), '</a>', 
         ' <a href="patch-display.php?patchname=',
             urlencode($patchname),
             '&bug_id=', $bug_id, '&diff=1&old=', $rev[0], '&revision=',
             $revision, '">[diff to current]</a></li>';
}
echo '</ul></li>';
?>
<h3>Developer: <a href="/user/<?php echo $handle; ?>"><?php echo $handle ?></a></h3>
<pre>
<?php echo htmlentities($patchcontents, ENT_QUOTES, 'UTF-8'); ?>
</pre>
