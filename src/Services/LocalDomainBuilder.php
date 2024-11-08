<?php

namespace App\Services;

/**
 * Class LocalDomainBuilder
 * @package AppBundle\Services
 */
class LocalDomainBuilder
{
    /**
     * @param string $siteId
     * @param string $platformVersion
     * @param $lang
     *
     * @return string
     */
    public function buildDomainName($siteId, $platformVersion, $lang = '')
    {

        $siteId = $this->sanitize($siteId);
        $platformVersion = $this->sanitize($platformVersion);

        if ($platformVersion > 1) {
            if ($lang) {
                $domain_name = sprintf('%s.%s.%s.local', $siteId, $platformVersion, $lang);
            }
            else {
                $domain_name = sprintf('%s.%s.local', $siteId, $platformVersion);
            }
        }
        else {
            if ($lang) {
               $domain_name = sprintf('%s.%s.local', $siteId, $lang);
            }
            else {
               $domain_name = sprintf('%s.local', $siteId);
            }
        }

        return $domain_name;
    }

    /**
     * Helper function for sanitization.
     *
     * @param string $text
     *   The text for sanitization.
     *
     * @return mixed
     */
    public function sanitize($text) {
        return preg_replace('/[^a-zA-Z0-9]+/i', '-', $text);
    }
}
