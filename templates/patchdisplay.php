<?php
response_header('Patch :: ' . clean($package_name) . ' :: Bug #' . $bug_id);
show_bugs_menu(clean($package_name));
?>
<h1>Patch version <?php echo format_date($revision) ?> for <?php echo clean($package_name); ?> Bug #<?php
    echo $bug_id; ?></h1>
<a href="bug.php?id=<?php echo urlencode($bug) ?>">Return to Bug #<?php echo $bug_id; ?></a>
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
<?php
echo '<ul>';
foreach ($revisions as $i => $rev) {
    echo '<li><a href="patch-display.php?bug_id=', urlencode($bug), '&amp;patch=',
         urlencode($patch), '&amp;revision=', urlencode($rev[0]), '">',
         format_date($rev[0]), '</a>',
         ' <a href="patch-display.php?patch=',
             urlencode($patch),
             '&amp;bug_id=', $bug, '&amp;diff=1&amp;old=', $rev[0], '&amp;revision=',
             $revision, '">[diff to current]</a></li>';
}
echo '</ul></li>';
?>
<h3>Developer: <?php echo $handle; ?></a></h3>
<pre>
<?php echo htmlentities($patchcontents, ENT_QUOTES, 'UTF-8'); ?>
</pre>
