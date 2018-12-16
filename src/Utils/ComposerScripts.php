<?php

namespace App\Utils;

use Composer\Script\Event;

/**
 * Service for running Composer scripts when installing application.
 */
class ComposerScripts
{
    private static $cacheDir = __DIR__.'/../../var/cache';
    private static $uploadsDir = __DIR__.'/../../uploads';

    /**
     * Create a default configuration settings for the development environment.
     */
    public static function installConfig(Event $event)
    {
        $sampleFile = __DIR__.'/../../local_config.php.sample';
        $targetFile = __DIR__.'/../../local_config.php';

        if ($event->isDevMode() && !file_exists($targetFile)) {
            copy($sampleFile, $targetFile);
        }
    }

    /**
     * Create application temporary and upload directories which are not tracked
     * in Git.
     */
    public static function createDirectories(Event $event)
    {
        if (!$event->isDevMode()) {
            return;
        }

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        require_once $vendorDir.'/autoload.php';

        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
            chmod(self::$cacheDir, 0777);
        }

        if (!file_exists(self::$uploadsDir)) {
            mkdir(self::$uploadsDir, 0777, true);
            chmod(self::$uploadsDir, 0777);
        }
    }
}
