<?php

namespace App\Model;

/**
 * Class NewClone
 *
 * @package AppBundle\Model
 */
class NewClone
{

    const DEBUG_CONTRIB_IMAGES = 'debug/images/contrib-structure/*';

    const DEBUG_CONTRIB_FONTS = 'debug/contrib-fonts/*';

    const DEBUG_STYLES_SASS = '/debug/styles/sass/**/*';

    const PATH_FOR_SYMLINK = '../../../../../docroot/sites';

    const BRAND_THEME = 'brand_theme';

    const PLATFORM_BASE_THEME = 'jj_gws';

    const EMPTY_FILE = '.gitkeep';

    public static $unusedFiles = [
        '/debug/images/*.jpg',
        '/debug/images/*.png',
        '/package-lock.json'
    ];

    public static $replacedFiles = [
        'placeholder.txt',
        '_brand_theme_example.scss'
    ];

    public static $themeNameLocates = [
        '/**/*.php',
        '/**/*preprocess.inc',
        '/*.info',
        '/*.rb',
        '/**/_fonts.scss'
    ];
}
