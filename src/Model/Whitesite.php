<?php

namespace App\Model;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class Whitesite
 *
 * @package AppBundle\Model
 */
class Whitesite
{

    const PLATFORM_THEME_DIR = 'docroot/profiles/jjbos/themes/jj_gws';

    /** Name for whitesite. */
    const PLATFORM_IDENTIFIER = 'whitesite';

    static function installWhitesite($siteDir)
    {
        $pathToInstallFile = $siteDir.'/scripts/install.sh';
        if (false == file_exists($pathToInstallFile)) {
            throw new FileNotFoundException(sprintf('Install file %s not found', $pathToInstallFile));
        }

        $drushCommand = file_get_contents($pathToInstallFile);

        return $drushCommand;
    }
}
