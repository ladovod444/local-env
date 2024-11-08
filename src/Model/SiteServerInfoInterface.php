<?php

namespace App\Model;

/**
 * Represents the site server model interface.
 */
Interface SiteServerInfoInterface
{

  /**
   * Get default platform version.
   *
   * @return string
   *   Default platform version.
   */
  public function getDefaultPlatformVersion();

  /**
   * Get the site id.
   *
   * @return string
   *   The site id.
   */
  public function getSiteId();
}
