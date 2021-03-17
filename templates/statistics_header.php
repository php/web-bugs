<th>
    <a href="/stats.php?sort_by=<?= $this->noHtml(urlencode($type)); ?>&rev=<?= $sortBy === $type ? (int) !$reverseSort : 1; ?>" class="bug_stats<?= $sortBy === $type ? '_choosen' : ''; ?>">
        <?= $this->e($type); ?>
    </a>
</th>
