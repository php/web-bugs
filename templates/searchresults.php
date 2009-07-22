<?php

if (!function_exists('show_prev_next')) {
    function show_prev_next($begin, $rows, $total_rows, $link, $limit)
{
    echo "<!-- BEGIN PREV/NEXT -->\n";
    echo " <tr>\n";
    echo '  <td class="search-prev_next" colspan="10">' . "\n";

    if ($limit=='All') {
        echo "$total_rows Bugs</td></tr>\n";
        return;
    }

    echo '   <table border="0" cellspacing="0" cellpadding="0" width="100%">' . "\n";
    echo "    <tr>\n";
    echo '     <td class="class-prev">';
    if ($begin > 0) {
        echo '<a href="' . $link . '&amp;begin=';
        echo max(0, $begin - $limit);
        echo '">&laquo; Show Previous ' . $limit . ' Entries</a>';
    } else {
        echo '&nbsp;';
    }
    echo "</td>\n";

    echo '     <td class="search-showing">Showing ' . ($begin+1);
    echo '-' . ($begin+$rows) . ' of ' . $total_rows . "</td>\n";

    echo '     <td class="search-next">';
    if ($begin+$rows < $total_rows) {
        echo '<a href="' . $link . '&amp;begin=' . ($begin+$limit);
        echo '">Show Next ' . $limit . ' Entries &raquo;</a>';
    } else {
        echo '&nbsp;';
    }
    echo "</td>\n    </tr>\n   </table>\n  </td>\n </tr>\n";
    echo "<!-- END PREV/NEXT -->\n";
}
}
?>
<table border="0" cellspacing="2" width="100%">

<?php show_prev_next($this->begin, $this->rows, $this->total_rows, $this->link, $this->limit); ?>

 <tr>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=bugb.id">ID#</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=ts1">Date</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=bugb.package">Package</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=bug_type">Type</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=status">Status</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=package_version">Package Version</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=php_version">PHP Version</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=php_os">OS</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=sdesc">Summary</a></th>
  <th class="results"><a href="<?php echo $this->link;?>&amp;reorder_by=assign">Assigned</a></th>
 </tr>
<?php

    foreach ($this->results as $row) {
        echo ' <tr valign="top" class="' . $this->tla[$row['status']] . '">' . "\n";

        /* Bug ID */
        echo '  <td align="center"><a href="bug.php?id='.$row['id'].'">'.$row['id'].'</a>';
        echo '<br /><a href="bug.php?id='.$row['id'].'&amp;edit=1">(edit)</a></td>' . "\n";

        /* Date */
        echo '  <td align="center">'.date ('Y-m-d H:i:s', strtotime ($row['ts1'])).'</td>' . "\n";
        echo '  <td>', htmlspecialchars($row['package_name']), '</td>' . "\n";
        echo '  <td>', htmlspecialchars(@$this->types[$row['bug_type']]), '</td>' . "\n";
        echo '  <td>', htmlspecialchars($row['status']);
        if ($row['status'] == 'Feedback' && isset($row['unchanged']) && $row['unchanged'] > 0) {
            printf ("<br />%d day%s", $row['unchanged'], $row['unchanged'] > 1 ? 's' : '');
        }
        echo '</td>' . "\n";
        echo '  <td>', htmlspecialchars($row['package_version']), '</td>';
        echo '  <td>', htmlspecialchars($row['php_version']), '</td>';
        echo '  <td>', $row['php_os'] ? htmlspecialchars($row['php_os']) : '&nbsp;', '</td>' . "\n";
        echo '  <td>', $row['sdesc']  ? clean($row['sdesc'])             : '&nbsp;', '</td>' . "\n";
        echo '  <td>', $row['assign'] ? htmlspecialchars($row['assign']) : '&nbsp;', '</td>' . "\n";
        echo " </tr>\n";
    }

    show_prev_next($this->begin, $this->rows, $this->total_rows, $this->link, $this->limit);

    echo "</table>\n\n";
?>
