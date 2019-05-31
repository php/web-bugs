<td class="bug_bg<?= $class; ?>">
    <?php if (!isset($statistics[$status])): ?>
        &nbsp;
    <?php elseif ($packageName === 'All'): ?>
        <a href="search.php?cmd=display&bug_type=<?= $this->noHtml(urlencode($bugType)); ?>&status=<?= $this->noHtml(urlencode($status)); ?>&by=Any&limit=30">
            <?= $statistics[$status]; ?>
        </a>
    <?php else: ?>
        <a href="search.php?cmd=display&bug_type=<?= $this->noHtml(urlencode($bugType)); ?>&status=<?= $this->noHtml(urlencode($status)); ?>&package_name[]=<?= $this->noHtml(urlencode($packageName)); ?>&by=Any&limit=30">
            <?= $statistics[$status]; ?>
        </a>
    <?php endif; ?>
</td>
