<?php $this->extends('layout.php', ['title' => 'Admin :: Database status']) ?>

<?php $this->start('content') ?>

<?php $this->include('pages/admin/menu.php', ['action' => $action]); ?>

<p>Running MySQL <b><?= $this->e($mysqlVersion); ?></b></p>

<h3>Number of rows:</h3>

<table>
    <tbody>
        <tr class="bug_header">
            <th>Table</th>
            <th>Rows</th>
        </tr>
        <?php foreach ($numberOfRowsPerTable as $tableName => $numberOfRows): ?>
            <tr>
                <td class="bug_bg0"><?= $this->e($tableName); ?></td>
                <td class="bug_bg1"><?= $this->e($numberOfRows); ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

<h3>Table status:</h3>

<table>
    <tbody>
        <tr class="bug_header">
            <th>Name</th>
            <th>Engine</th>
            <th>Version</th>
            <th>Row_format</th>
            <th>Rows</th>
            <th>Avg_row_length</th>
            <th>Data_length</th>
            <th>Max_data_length</th>
            <th>Index_length</th>
            <th>Data_free</th>
            <th>Auto_increment</th>
            <th>Create_time</th>
            <th>Update_time</th>
            <th>Check_time</th>
            <th>Collation</th>
            <th>Checksum</th>
            <th>Create_options</th>
            <th>Comment</th>
        </tr>
        <?php foreach ($statusPerTable as $tableStatus): ?>
            <tr>
                <td class="bug_bg0"><?= $this->e($tableStatus['Name']); ?></td>
                <td class="bug_bg1"><?= $this->e($tableStatus['Engine']); ?></td>
                <td class="bug_bg0"><?= $tableStatus['Version']; ?></td>
                <td class="bug_bg1"><?= $this->e($tableStatus['Row_format']); ?></td>
                <td class="bug_bg0"><?= $tableStatus['Rows']; ?></td>
                <td class="bug_bg1"><?= $tableStatus['Avg_row_length']; ?></td>
                <td class="bug_bg0"><?= $tableStatus['Data_length']; ?></td>
                <td class="bug_bg1"><?= $tableStatus['Max_data_length']; ?></td>
                <td class="bug_bg0"><?= $tableStatus['Index_length']; ?></td>
                <td class="bug_bg1"><?= $tableStatus['Data_free']; ?></td>
                <td class="bug_bg0"><?= $tableStatus['Auto_increment']; ?></td>
                <td class="bug_bg1"><?= $this->e($tableStatus['Create_time']); ?></td>
                <td class="bug_bg0"><?= $this->e($tableStatus['Update_time']); ?></td>
                <td class="bug_bg1"><?php echo $tableStatus['Check_time'] ? $this->e($tableStatus['Check_time']) : ''; ?></td>
                <td class="bug_bg0"><?= $this->e($tableStatus['Collation']); ?></td>
                <td class="bug_bg1"><?php echo $tableStatus['Checksum'] ? $this->e($tableStatus['Checksum']) : ''; ?></td>
                <td class="bug_bg0"><?= $this->e($tableStatus['Create_options']); ?></td>
                <td class="bug_bg1"><?= $this->e($tableStatus['Comment']); ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php $this->end('content') ?>
