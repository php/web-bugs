
set -e
set -x

# Generate a local_config.php if it doesn't already exist.
if [ ! -f "/var/app/local_config.php" ]; then
    echo "<?php" > /var/app/local_config.php
    echo "" >> /var/app/local_config.php
    echo "/**" >> /var/app/local_config.php
    echo " * Generated from containers/installer/entrypoint.sh" >> /var/app/local_config.php

    echo " * Feel free to edit. Delete to have it re-generated from" >> /var/app/local_config.php
    echo " * scratch on next 'docker-compose up'" >> /var/app/local_config.php
    echo " */" >> /var/app/local_config.php
    echo "" >> /var/app/local_config.php
    echo "\$site_data = [" >> /var/app/local_config.php
    echo "    'method' => 'http'," >> /var/app/local_config.php
    echo "    'url' => '127.0.0.1'," >> /var/app/local_config.php
    echo "    'basedir' => ''," >> /var/app/local_config.php
    echo "    'email' => 'php-bugs@lists.php.net'," >> /var/app/local_config.php
    echo "    'doc_email' => 'doc-bugs@lists.php.net'," >> /var/app/local_config.php
    echo "    'security_email' => 'security@php.net'," >> /var/app/local_config.php
    echo "    'db' => 'phpbugsdb'," >> /var/app/local_config.php
    echo "    'db_user' => 'nobody'," >> /var/app/local_config.php
    echo "    'db_pass' => 'bugs_password'," >> /var/app/local_config.php
    echo "    'db_host' => 'db'," >> /var/app/local_config.php
    echo "    'patch_tmp' => \"{\$ROOT_DIR}/uploads/patches/\"," >> /var/app/local_config.php
    echo "];" >> /var/app/local_config.php
    echo "" >> /var/app/local_config.php
    echo "define('DEVBOX', true);" >> /var/app/local_config.php
fi


if [ ! -f "composer.phar" ]; then
    echo "composer.phar does not exist, downloading.";
    curl "https://getcomposer.org/download/2.0.9/composer.phar" -o composer.phar
fi
php composer.phar install

echo "Installer is finished."
