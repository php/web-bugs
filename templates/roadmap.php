<?php
response_header('Roadmap :: ' . clean($this->package));
show_bugs_menu(clean($this->package));
?>
<h1>Roadmap for Package <?php echo clean($this->package); ?></h1>
<a href="roadmap.php?showold=1&amp;package=<?php echo urlencode($this->package) ?>">Show Old Roadmaps</a>
<?php if ($GLOBALS['auth_user']) { ?>
 | <a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&amp;new=1">New roadmap</a>
<?php
}

foreach ($this->roadmap as $info):
if (in_array($info['roadmap_version'], $this->releases)) {
    if (!$this->showold) {
        continue;
    } else {
        $showold = '&amp;showold=1';
    }
} else {
    $showold = '';
}
    $future = ($info['releasedate'] == '1976-09-02 17:15:30');
    $x = ceil((((strtotime($info['releasedate']) - time()) / 60) / 60) / 24);
?>
<a name="a<?php echo $info['roadmap_version'] ?>"></a>
<h2>Version <?php echo $info['roadmap_version'] ?>
 <span style="font-size: 77%; font-weight: normal; color: black;">
 (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)
 </span>
</h2>
<table style="width: 100%;">
 <tr>
  <td colspan="2">
   <?php if ($GLOBALS['auth_user']) : ?>
   <a href="roadmap.php?package=<?php echo urlencode($this->package). $showold ?>&amp;addbugs=1&amp;roadmap=<?php
    echo urlencode($info['roadmap_version']) ?>">Add Bugs/Features to this Roadmap</a>
   <?php endif; ?>
   <?php if (auth_check('pear.dev')) : ?>
   | <a href="roadmap.php?package=<?php echo urlencode($this->package). $showold ?>&amp;packagexml=1&amp;roadmap=<?php
    echo urlencode($info['roadmap_version']) ?>">Generate package.xml for this release</a>
   <?php endif; ?>
  </td>
 </tr>
 <tr>
  <td class="form-input">
   <strong>Scheduled Release Date:</strong> <span<?php
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
        if ($x != 1) {
            echo 's';
        }

        if ($x < 0) {
            echo '!!';
        }
        echo ')';
    } ?></span>
  </td>
 </tr>
 <tr>
  <td class="form-input">
   <strong>Release Goals:</strong><br />
   <pre><?php echo htmlspecialchars($info['description']); ?></pre>
  </td>
 </tr>
 <tr>
  <td class="form-input">
   <br /><h3>Bugs</h3>
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
   ?>) <a href="?roadmapdetail=<?php echo htmlspecialchars(urlencode($info['roadmap_version'])). $showold ?>&amp;package=<?php echo $this->package . '#a'. $info['roadmap_version']; ?>">Show Bug Detail</a>
      <?php endif;//if (!$this->totalbugs[$info['roadmap_version']])
         else: //if ($this->summary[$info['roadmap_version']])
            echo $this->bugs[$info['roadmap_version']];
         endif; //if ($this->summary[$info['roadmap_version']]) ?>
  </td>
 </tr>
 <tr>
  <td class="form-input">
    <br /><h3>Feature Requests</h3>
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
   ?>) <a href="?roadmapdetail=<?php echo htmlspecialchars(urlencode($info['roadmap_version'])) ?>&amp;package=<?php echo $this->package. $showold . '#a'. $info['roadmap_version'];  ?>">Show Feature Detail</a>
      <?php endif;//if (!$this->totalfeatures[$info['roadmap_version']])
         else: //if ($this->summary[$info['roadmap_version']])
            echo $this->feature_requests[$info['roadmap_version']];
         endif; //if ($this->summary[$info['roadmap_version']]) ?>
  </td>
 </tr>
</table>
<?php endforeach; // foreach ($this->versions) ?>
<?php response_footer(); ?>
