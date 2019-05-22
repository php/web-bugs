<p>
    <a href="/admin/?action=phpinfo" <?= ('phpinfo' === $action) ? 'class="active"' : ''; ?>>
        phpinfo()
    </a>
    |
    <a href="/admin/?action=list_lists" <?= ('list_lists' === $action) ? 'class="active"' : ''; ?>>
        Package mailing lists
    </a>
    |
    <a href="/admin/?action=list_responses" <?= ('list_responses' === $action) ? 'class="active"' : ''; ?>>
        Quick fix responses
    </a>
    |
    <a href="/admin/?action=mysql" <?= ('mysql' === $action) ? 'class="active"' : ''; ?>>
        Database status
    </a>
</p>
