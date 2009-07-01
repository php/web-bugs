<?php response_header('Roadmap :: ' . htmlspecialchars($templateData->package));?>
<h1>package.xml for Package <?php echo htmlspecialchars($templateData->package); ?>, version <?php echo htmlspecialchars($templateData->roadmap) ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(htmlspecialchars($templateData->package)) ?>&status=Open&cmd=display">Bug Tracker</a>
<ul class="side_pages">
 <li><a href="roadmap.php?package=<?php echo urlencode($templateData->package) ?>">Back to roadmap list</a></li>
</ul>
<h2>package.xml:</h2>
<pre>
<?php echo htmlspecialchars($templateData->xml) ?>
</pre>
<?php response_footer(); ?>
