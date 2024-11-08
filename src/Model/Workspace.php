<?php

namespace App\Model;

/**
 * Class Workspace
 *
 * @package AppBundle\Model
 */
class Workspace
{

    const BASE_DIR = 'var/workspace';

    const PLATFORM_DIR = 'var/workspace/platform';

    const CONFIG_DIR = 'app/config';

    const VAULT_CONFIG = 'vault.inc';

    /**
     * @deprecated
     */
    const PLATFORM_TAGS = 'var/workspace/platform-tags';

    /**
     * @deprecated
     */
    const BUILD_DIR = 'var/workspace/build';

    const FILE_TEMPLATES = 'app/Resources/templates';

    const CLUSTER_DIR = '../cluster';

    const FILES_DIR = 'var/workspace/backups/files';

    const DB_DIR = 'var/workspace/backups/db';
}
