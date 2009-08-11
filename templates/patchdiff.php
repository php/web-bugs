<?php
response_header('Patch :: ' . clean($package) . ' :: Bug #' . clean($bug));
show_bugs_menu(clean($package));
?>
<h1>Patch version <?php echo format_date($revision) ?> for <?php echo clean($package) ?> Bug #<?php
    echo clean($bug) ?></h1>
<a href="bug.php?id=<?php echo urlencode($bug) ?>">Return to Bug #<?php echo clean($bug) ?></a>
| <a href="patch-display.php?bug_id=<?php echo urlencode($bug) ?>&amp;patch=<?php echo urlencode($patch)
    ?>&amp;revision=<?php echo urlencode($revision) ?>&amp;download=1">Download this patch</a><br />
<?php
if (count($obsoletedby)) {
    echo '<div class="warnings">This patch is obsolete</div><p>Obsoleted by patches:<ul>';
    foreach ($obsoletedby as $betterpatch) {
        echo '<li><a href="patch-display.php?patch=',
             urlencode($betterpatch['patch']),
             '&amp;bug_id=', $bug, '&amp;revision=', $betterpatch['revision'],
             '">', htmlspecialchars($betterpatch['patch']), ', revision ',
             format_date($betterpatch['revision']), '</a></li>';
    }
    echo '</ul></p>';
}
if (count($obsoletes)) {
    echo '<div class="warnings">This patch renders other patches obsolete</div>',
         '<p>Obsolete patches:<ul>';
    foreach ($obsoletes as $betterpatch) {
        echo '<li><a href="patch-display.php?patch=',
             urlencode($betterpatch['obsolete_patch']),
             '&amp;bug_id=', $bug,
             '&amp;revision=', $betterpatch['obsolete_revision'],
             '">', htmlspecialchars($betterpatch['obsolete_patch']), ', revision ',
             format_date($betterpatch['obsolete_revision']), '</a></li>';
    }
    echo '</ul></p>';
}
?>
Patch Revisions:
<?php foreach ($revisions as $i => $revision) { ?>
<a href="patch-display.php?bug_id=<?php echo urlencode($bug) ?>&amp;patch=<?php
    echo urlencode($patch) ?>&amp;revision=<?php echo urlencode($revision[0]); ?>"><?php
    echo format_date($revision[0]) ?></a><?php if ($i < count($revisions) - 1) echo ' | '; ?>
<?php } ?>
<h3>Developer: <?php echo $handle; ?></h3>
<pre>
<?php if ($d->isEmpty()) echo 'Diffs are identical!'; else {
    echo $diff->render($d);
}
?>
</pre>
