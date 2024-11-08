<?php

namespace App\Controller;

use App\Model\SiteDir;
use App\Services\LocalSiteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A controller gathers sites information and represents as JSON.
 */
class SitesController extends AbstractController
{

    /**
     * The local site manager.
     *
     * @var \App\Services\LocalSiteManager;
     */
    protected $localSiteManager;

    /**
     * The cluster directory path.
     *
     * @var string
     */
    protected $clusterDirectory;

    /**
     * Constructs the SitesController object.
     *
     * @param LocalSiteManager $localSiteManager
     *   The local site manager.
     */
    public function __construct(LocalSiteManager $localSiteManager)
    {
        $this->clusterDirectory = realpath(getcwd() . '/../../cluster');
        $this->localSiteManager = $localSiteManager;
    }

    /**
     * Returns a list of available sites.
     *
     * @param LocalSiteManager $localSiteManager
     *   The local site manager.
     *
     * @return JsonResponse
     */
    public function listAction(LocalSiteManager $localSiteManager)
    {
        $sites = [];

        foreach (glob($this->clusterDirectory . '/*', GLOB_ONLYDIR) as $sitesDir) {
            $dirName = substr($sitesDir, strripos($sitesDir, '/') + 1);

            // Sites with specific structure are supported only.
            if (!preg_match('/_\d+$/', $dirName)) {
                continue;
            }

            // Get all available links.
            $links = [];
            foreach ($localSiteManager->getDomains($sitesDir) as $domain) {
                array_push($links, "http://{$domain}", "https://{$domain}");
            }

            $sites[] = new SiteDir(
                $dirName,
                $this->localSiteManager->extractSiteIdFromDir($dirName, true),
                $this->localSiteManager->buildVersion($this->clusterDirectory . '/' . $dirName),
                $links,
                $sitesDir
            );
        }

        return $this->json($sites, Response::HTTP_OK, ['Access-Control-Allow-Origin' => '*']);
    }
}
