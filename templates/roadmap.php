<?php response_header('Roadmap :: ' . htmlspecialchars($this->package));?>
<h1>Roadmap for Package <?php echo htmlspecialchars($this->package); ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(htmlspecialchars($this->package)) ?>&status=Open&cmd=display">Bug Tracker</a> | <a href="/<?php echo urlencode(htmlspecialchars($this->package)) ?>">Package Home</a> | <a href="roadmap.php?showold=1&package=<?php echo urlencode($this->package) ?>">Show Old Roadmaps</a>
<?php if ($GLOBALS['auth_user']) { ?>
<ul class="side_pages">
<?php foreach ($this->roadmap as $info):
if (in_array($info['roadmap_version'], $this->releases)) {
    if (!$this->showold) continue;
}
$future = ($info['releasedate'] == '1976-09-02 17:15:30');
?>
 <li class="side_page"><a href="#a<?php echo $info['roadmap_version'] ?>"><?php echo $info['roadmap_version'] ?></a> (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)</li>
<?php endforeach; ?>
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&new=1">New roadmap</a></li>
</ul>
<?php
}

foreach ($this->roadmap as $info):
if (in_array($info['roadmap_version'], $this->releases)) {
    if (!$this->showold) {
        continue;
    } else {
        $showold = '&showold=1';
    }
} else {
    $showold = '';
}
    $future = ($info['releasedate'] == '1976-09-02 17:15:30');
    $x = ceil((((strtotime($info['releasedate']) - time()) / 60) / 60) / 24);
?>
<a name="a<?php echo $info['roadmap_version'] ?>"></a>
<h2>Version <?php echo $info['roadmap_version'] ?></h2>
<table>
 <tr>
  <td colspan="2">
   <?php if ($GLOBALS['auth_user']) : ?>
   <a href="roadmap.php?package=<?php echo urlencode($this->package). $showold ?>&addbugs=1&roadmap=<?php
    echo urlencode($info['roadmap_version']) ?>">Add Bugs/Features to this Roadmap</a><br />
   <?php endif; ?>
   <?php if (auth_check('pear.dev')) : ?>
   <a href="roadmap.php?package=<?php echo urlencode($this->package). $showold ?>&packagexml=1&roadmap=<?php
    echo urlencode($info['roadmap_version']) ?>">Generate package.xml for this release</a>
   <?php endif; ?>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Scheduled Release Date
  </th>
  <td class="form-input">
   <strong<?php
    if (!$future) {
        if ($x < 0) {
            echo ' class="lateRelease"';
        }
    } ?>><?php
    if ($future) {
        echo 'future';
    } else {
        echo date('Y-m-d', strtotime($info['releasedate'])) .
                  ' (' . $x . ' day';
        if ($x != 1) echo 's';
        if ($x < 0) echo '!!';
        echo ')';
    } ?></strong>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Release Goals
  </th>
  <td class="form-input">
   <pre><?php echo htmlspecialchars($info['description']); ?></pre>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Bugs
  </th>
  <td class="form-input">
   <?php if ($this->summary[$info['roadmap_version']]):
            if (!$this->totalbugs[$info['roadmap_version']]): ?>
   No bugs
      <?php else://if (!$this->totalbugs[$info['roadmap_version']])
                $percent = 100 * ($this->closedbugs[$info['roadmap_version']] /
                    $this->totalbugs[$info['roadmap_version']]);
            ?>
   (<?php echo number_format($percent)?>% done: <?php
   echo $this->closedbugs[$info['roadmap_version']] ?> fixed of <?php
   echo $this->totalbugs[$info['roadmap_version']]
   ?>) <a href="?roadmapdetail=<?php echo htmlspecialchars(urlencode($info['roadmap_version'])). $showold ?>&package=<?php echo $this->package . '#a'. $info['roadmap_version']; ?>">Show Bug Detail</a>
      <?php endif;//if (!$this->totalbugs[$info['roadmap_version']])
         else: //if ($this->summary[$info['roadmap_version']])
            echo $this->bugs[$info['roadmap_version']];
         endif; //if ($this->summary[$info['roadmap_version']]) ?>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Feature Requests
  </th>
  <td class="form-input">
   <?php if ($this->summary[$info['roadmap_version']]):
            if (!$this->totalfeatures[$info['roadmap_version']]): ?>
   No features
      <?php else://if (!$this->totalfeatures[$info['roadmap_version']])
                $percent = 100 * ($this->closedfeatures[$info['roadmap_version']] /
                    $this->totalfeatures[$info['roadmap_version']]);
            ?>
   (<?php echo number_format($percent)?>% done: <?php
   echo $this->closedfeatures[$info['roadmap_version']] ?> implemented of <?php
   echo $this->totalfeatures[$info['roadmap_version']]
   ?>) <a href="?roadmapdetail=<?php echo htmlspecialchars(urlencode($info['roadmap_version'])) ?>&package=<?php echo $this->package. $showold . '#a'. $info['roadmap_version'];  ?>">Show Feature Detail</a>
      <?php endif;//if (!$this->totalfeatures[$info['roadmap_version']])
         else: //if ($this->summary[$info['roadmap_version']])
            echo $this->feature_requests[$info['roadmap_version']];
         endif; //if ($this->summary[$info['roadmap_version']]) ?>
  </td>
 </tr>
</table>
<?php endforeach; // foreach ($this->versions) ?>
<?php response_footer(); ?>
