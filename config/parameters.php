<?php

/**
 * Application configuration parameters.
 */

return [
    /**
     * Application environment. Can be one of ['dev', 'prod'].
     */
    'env' => (defined('DEVBOX') && true === DEVBOX) ? 'dev' : 'prod',

    /**
     * Site scheme - http or https.
     */
    'site_scheme' => $site_data['method'],

    /**
     * Site URL.
     */
    'site_url' => $site_data['url'],

    /**
     * Site base path if present. Part that comes after the domain
     * https://bugs.php.net/basedir/
     */
    'basedir' => $site_data['basedir'],

    /**
     * Database username.
     */
    'db_user' => $site_data['db_user'],

    /**
     * Database password.
     */
    'db_password' => $site_data['db_pass'],

    /**
     * Database host name.
     */
    'db_host' => $site_data['db_host'],

    /**
     * Database name.
     */
    'db_name' => $site_data['db'],

    /**
     * Main email of the public mailing list.
     */
    'email'=> $site_data['email'],

    /**
     * Email of the public mailing list for documentation related bugs.
     */
    'doc_email' => $site_data['doc_email'],

    /**
     * Security email - emails sent to this are not visible in public.
     */
    'security_email' => $site_data['security_email'],

    /**
     * Uploads directory location.
     */
    'uploads_dir' => $site_data['patch_tmp'],

    /**
     * Templates directory.
     */
    'templates_dir' => __DIR__.'/../templates',
];
