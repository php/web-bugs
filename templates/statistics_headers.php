<tr class='bug_header'>
    <th><?= (!isset($isSubHeader) || $isSubHeader !== true) ? 'Name' : '&nbsp;'; ?></th>
    <th>&nbsp;</th>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Closed']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Open']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Critical']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Verified']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Analyzed']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Assigned']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Feedback']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'No Feedback']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Suspended']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Not a bug']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Duplicate']); ?>
    <?php $this->include('statistics_header.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort, 'type' => 'Wont fix']); ?>
</tr>
