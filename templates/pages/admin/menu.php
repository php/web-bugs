<p>
    <a href="/admin/?action=phpinfo" <?php if ($_GET['action'] === 'phpinfo') echo 'class="active"'; ?>>
        phpinfo()
    </a>
    |
    <a href="/admin/?action=list_lists" <?php if ($_GET['action'] === 'list_lists') echo 'class="active"'; ?>>
        Package mailing lists
    </a>
    |
    <a href="/admin/?action=list_responses" <?php if ($_GET['action'] === 'list_responses') echo 'class="active"'; ?>>
        Quick fix responses
    </a>
    |
    <a href="/admin/?action=mysql" <?php if ($_GET['action'] === 'mysql') echo 'class="active"'; ?>>
        Database status
    </a>
</p>
