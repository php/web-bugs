<?php $this->extends('layout.php', ['title' => 'Login']) ?>

<?php $this->start('content') ?>

<?php if ($invalidLogin): ?>
    <div style="background: #AB1616; padding: 3px; width: 300px; color: #FFF; margin: 3px;">
        Wrong username or password!
    </div>
<?php endif; ?>
<form method="post" action="login.php">
    <input type="hidden" name="referer" value="<?= $this->e($referer); ?>">
    <table>
        <tr>
            <th align="right">Username:</th>
            <td><input type="text" name="user" value="<?= $this->e($username); ?>">@php.net
        </tr>
        <tr>
            <th align="right">Password:</th>
            <td><input type="password" name="pw">
        </tr>
        <tr>
            <td align="center" colspan="2"><input type="submit" value="Login"></td>
        </tr>
    </table>
</form>

<?php $this->end('content') ?>
