<?php

namespace App\Services;

use App\Model\SiteServerInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Unirest\Request;

/**
 * Represents a service to extract site information.
 */
class SiteInfoExtractor
{
    /**
     * The authentication username.
     *
     * @var string
     */
    private $authUser;

    /**
     * The authentication password.
     *
     * @var string
     */
    private $authPwd;

    /**
     * CTECH Data Provider.
     *
     * @var \App\Services\DataProviderBridge
     */
    private $dataProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructs the SiteInfoExtractor object.
     *
     * @param string $authUser
     *   The authentication username.
     * @param string $authPwd
     *   The authentication password.
     * @param \App\Services\DataProviderBridge $dataProvider
     *   CTECH Data Provider.
     */
    public function __construct($authUser, $authPwd, DataProviderBridge $dataProvider, LoggerInterface $logger)
    {
        $this->authUser = $authUser;
        $this->authPwd = $authPwd;
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
    }

    /**
     * @param string $siteUrl
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getMonitorInfo($siteUrl)
    {
        $monitorDataInfo = [];
        try {
            $monitorDataInfo['site'] = $this->dataProvider->getLocationPropertyByUrl($siteUrl, 'subscription');
            $monitorDataInfo['hostname'] = $this->dataProvider->getEnvironmentPropertyByHostName($siteUrl, 'settings.remote_host');
        } catch (\Exception $e) {
            throw  $e;
        }

        return $monitorDataInfo;
    }

    /**
     * Get the server info.
     *
     * @param string $siteUrl
     *   The site URL.
     * @param string $theme
     *   The theme name.
     *
     * @return \App\Model\SiteServerInfo
     *   The site server information.
     *
     * @throws \Exception
     */
    public function getServerInfo($siteUrl, $theme = '')
    {
        try {
            $domainInfo = $this->dataProvider->getDomainInfo($siteUrl);
            if ($domainInfo === 401) {
                $this->logger->info('<error>JnJ credentials are not valid, please edit parameters.yml file</error>');
                exit;
            }
            // Get the environment information for a specific domain.
            $envInfo = $this->dataProvider->getEnvironmentInfo(
                $domainInfo->domain->location->subscription,
                $domainInfo->domain->location->environment,
                $domainInfo->domain->name
            );
        } catch (\Exception $e) {
            throw new \Exception("Looks like this operation met error on Dataprovider side. Unfortunately, the site can't be recognized. Deploy won't start.");
        }

        $siteId = $envInfo->settings->location;

        // Unfortunately, we can't extract a theme using ctech-dataprovider. So,
        // let's parse HTML page for such purpose.
        // @todo: replace it for better method if possible!
        if (!$theme) {
            Request::curlOpt(CURLOPT_SSL_VERIFYPEER, 0);
            Request::curlOpt(CURLOPT_SSL_VERIFYHOST, false);
            Request::auth($this->authUser, $this->authPwd);

            $response = Request::get($siteUrl);
            $statusOk = ($response->code === Response::HTTP_OK);

            if (!$statusOk || !($theme = $this->extractThemeName($response->body, $siteId))) {
                throw new \Exception("The theme can't be detected! Please check app_acquia_site_auth_user/app_acquia_site_auth_pwd in parameters.yml or use extra option to specify theme manually, i.e: --theme=<my_theme>");
            }
        }

        return new SiteServerInfo(
            $siteUrl,
            $domainInfo->domain->location->environment,
            $envInfo->platform_definition->package->version,
            $siteId,
            $theme
        );
    }

    /**
     * Detect the theme name by a document source code.
     *
     * The only way to detect a theme name by a source code is to track a path
     * to release (debug) directory. A base theme (jj_gws) adds some files
     * there, which are not aggregated. So, we can rely on it.
     *
     * For unpredictable situations we can specify a theme manually using an
     * extra --theme option!
     *
     * @param string $document
     *   The document source code.
     * @param string $siteId
     *   The site ID.
     *
     * @return string|null
     *   The theme name if it was detected, otherwise - null.
     */
    protected function extractThemeName($document, $siteId)
    {
        $regex = '/sites\/' . $siteId . '\/themes\/(?<theme>.+?)\/(release|debug)/';
        preg_match($regex, $document, $matches);

        return !empty($matches['theme']) ? $matches['theme'] : null;
    }
}
