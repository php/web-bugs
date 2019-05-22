<?php $this->extends('layout.php', ['title' => 'Admin :: Quick fix responses']) ?>

<?php $this->start('content') ?>

<?php $this->include('pages/admin/menu.php', ['action' => $action]); ?>

<h3>List Responses</h3>

<table>
    <tbody>
        <tr class="bug_header">
            <th>id</th>
            <th>name</th>
            <th>status</th>
            <th>title</th>
            <th>message</th>
            <th>project</th>
            <th>package_name</th>
            <th>webonly</th>
        </tr>
        <?php foreach ($responses as $response): ?>
            <tr>
                <td class="bug_bg0"><?= $response['id']; ?></td>
                <td class="bug_bg1"><?= $this->e($response['name']); ?></td>
                <td class="bug_bg0"><?php echo $response['status'] ? $this->e($response['status']) : ''; ?></td>
                <td class="bug_bg1"><?= $this->e($response['title']); ?></td>
                <td class="bug_bg0 tbl-row-message">
                    <?= nl2br($this->e($response['message'])); ?>
                </td>
                <td class="bug_bg1"><?= $this->e($response['project']); ?></td>
                <td class="bug_bg0"><?php echo $response['package_name'] ? $this->e($response['package_name']) : ''; ?></td>
                <td class="bug_bg1"><?= $response['webonly']; ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php $this->end('content') ?>
