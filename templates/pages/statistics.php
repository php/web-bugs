<?php $this->extends('layout.php', ['title' => 'Bugs Stats']) ?>

<?php $this->start('content') ?>

<form method="get" action="stats.php">
    <table>
        <tr>
            <td style="white-space: nowrap">
                <strong>Bug Type:</strong>
                <select class="small" id="bug_type" name="bug_type" onchange="this.form.submit(); return false;">
                    <?php $this->include('bug_type_options.php', ['enableAllOption' => true, 'selectedType' => $selectedType]); ?>
                </select>
                <input class="small" type="submit" name="submitStats" value="Search">
            </td>
        </tr>
    </table>
</form>

<table style="width: 100%; margin-top: 1em;" class="stats-table">
    <?php if ($statistics['All']['Total'] === 0): ?>
        <tr>
            <td>No bugs found</td>
        </tr>
    <?php else: ?>
        <?php $this->include('statistics_headers.php', ['sortBy' => $sortBy, 'reverseSort' => $reverseSort]); ?>
        <?php $rowCount = 0; ?>
        <?php foreach($statistics as $packageName => $packageStatistics): ?>
            <?php $rowCount++; ?>
            <?php if ($rowCount % 40 === 0): ?>
                <?php $this->include('statistics_headers.php', ['isSubHeader' => true, 'sortBy' => $sortBy, 'reverseSort' => $reverseSort]); ?>
            <?php endif; ?>
            <tr>
                <td class="bug_head"><?= $this->e($packageName); ?></td>
                <td class="bug_bg0"><?= $packageStatistics['Total']; ?></td>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Closed']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Open']); ?>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Critical']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Verified']); ?>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Analyzed']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Assigned']); ?>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Feedback']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'No Feedback']); ?>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Suspended']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Not a bug']); ?>
                <?php $this->include('statistics_value.php', ['class' => 1, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Duplicate']); ?>
                <?php $this->include('statistics_value.php', ['class' => 0, 'packageName' => $packageName, 'statistics' => $packageStatistics, 'bugType' => $selectedType, 'status' => 'Wont fix']); ?>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php if ($statistics['All']['Total'] !== 0): ?>
    <hr>

    <p><b>PHP Versions for recent bug reports:</b></p>

    <?php foreach ($recentReports as $date => $recentReportsOfDate): ?>
        <table style="float:left; margin-right:20px">
            <tr class='bug_header'>
                <th colspan='2'>
                    <?= $this->e($date); ?>
                </th>
            </tr>
            <?php foreach ($recentReportsOfDate as $versionInformation): ?>
                <tr>
                    <td class='bug_head'>
                        <?= $this->e($versionInformation['version']); ?>
                    </td>
                    <td class='bug_bg1'>
                        <?= $versionInformation['quantity']; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<?php $this->end('content') ?>
