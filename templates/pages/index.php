<?php $this->extends('layout.php', ['title' => 'Bugs homepage']) ?>

<?php $this->start('content') ?>

<h1>PHP Bug Tracking System</h1>

<p>Before you report a bug, please make sure you have completed the following
steps:</p>

<ul>
    <li>
        Used the form above or our <a href="/search.php">advanced search page</a>
        to make sure nobody has reported the bug already.
    </li>

    <li>
        Make sure you are using the latest stable version or a build from Git,
        if similar bugs have recently been fixed and committed.
    </li>

    <li>
        Read our tips on <a href="/how-to-report.php">how to report a bug that
        someone will want to help fix</a>.
    </li>

    <li>
        Read the <a href="https://wiki.php.net/security">security guidelines</a>,
        if you think an issue might be security related.
    </li>

    <li>
        See how to get a backtrace in case of a crash:
        <a href="/bugs-generating-backtrace.php">for *NIX</a> and
        <a href="/bugs-generating-backtrace-win32.php">for Windows</a>.
    </li>

    <li>
        Make sure it isn't a support question. For support, see the
        <a href="https://php.net/support.php">support page</a>.
    </li>
</ul>

<p>Once you've double-checked that the bug you've found hasn't already been
reported, and that you have collected all the information you need to file an
excellent bug report, you can do so on our <a href="/report.php">bug reporting
page</a>.</p>

<h1>Search the Bug System</h1>

<p>You can search all of the bugs that have been reported on our
<a href="/search.php">advanced search page</a>, or use the form at the top of the
page for a basic default search. Read the <a href="/search-howto.php">search howto</a>
for instructions on how search works.</p>

<p>If you have 10 minutes to kill and you want to help us out, grab a random
open bug and see if you can help resolve it. We have made it easy. Hit
<a href="/random">random</a> to go directly to a random open bug.</p>

<p>Common searches</p>

<ul>
    <?php foreach ($searches as $title => $link): ?>
        <li><a href="<?= $this->noHtml($link) ?>"><?= $this->e($title) ?></a></li>
    <?php endforeach ?>
</ul>

<h1>Bug System Statistics</h1>

<p>You can view a variety of statistics about the bugs that have been reported
on our <a href="/stats.php">bug statistics page</a>.</p>

<?php $this->end('content') ?>

<?php $this->start('scripts') ?>
    <script src="/js/redirect.js"></script>
<?php $this->end('scripts') ?>
