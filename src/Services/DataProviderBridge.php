<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class DataProviderBridge
 *
 * @package AppBundle\Services
 */
class DataProviderBridge
{

  private $dataproviderUrl;

  private $jnjUser;

  private $jnjPwd;

  /**
   * DataProviderBridge constructor.
   *
   * @param $dataproviderUrl
   * @param $jnjUser
   * @param $jnjPwd
   */
  public function __construct($dataproviderUrl, $jnjUser, $jnjPwd)
  {
    $this->dataproviderUrl = $dataproviderUrl;
    $this->jnjUser = $jnjUser[0];
    $this->jnjPwd = $jnjPwd[0];
  }

  /**
   * @param string $url
   * @param string $property
   *
   * @return mixed
   * @throws \Exception
   */
  public function getLocationPropertyByUrl($url, $property)
  {
    $host = parse_url($url, PHP_URL_HOST);

    $url = sprintf('%s/api/domains/%s', $this->dataproviderUrl, $host);

    //dump($url);

    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $url, ['auth' => [$this->jnjUser, $this->jnjPwd]]);
    if (false == in_array($response->getStatusCode(), [Response::HTTP_OK])) {
      throw new \Exception(
        sprintf('Invalid response code: %s. Request url: %s', $response->code, $url)
      );
    }

    $body = json_decode($response->getBody()->getContents());
    if (empty($body->domain->location->{$property})) {
      throw new \Exception(
        sprintf('Could not detect subscription. Check response %s', serialize($response->body))
      );
    }

    return $body->domain->location->{$property};
  }

  /**
   * @param string $host
   * @param string $propertyPath
   *
   * @return mixed|null
   * @throws \Exception
   */
  public function getEnvironmentPropertyByHostName($host, $propertyPath)
  {
    $propertyAccessor = PropertyAccess::createPropertyAccessor();

    $subscription = $this->getLocationPropertyByUrl($host, 'subscription');
    $environment = $this->getLocationPropertyByUrl($host, 'environment');
    $url = sprintf(
      '%s/api/subscriptions/%s/environments/%s',
      $this->dataproviderUrl,
      $subscription,
      $environment
    );

    dump($url);
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $url, ['auth' => [$this->jnjUser, $this->jnjPwd]]);
    $body = json_decode($response->getBody()->getContents());

    if ($propertyAccessor->isReadable($body, $propertyPath)) {
      return $propertyAccessor->getValue($body, $propertyPath);
    }

    return null;
  }

  /**
   * Get domain information.
   *
   * @param string $url
   *   The site URL.
   *
   * @return \stdClass|int
   *   The domain info or response code in case of failure.
   */
  public function getDomainInfo($url)
  {
    $host = parse_url($url, PHP_URL_HOST);
    $url = sprintf('%s/api/domains/%s', $this->dataproviderUrl, $host);

    $client = new \GuzzleHttp\Client();
    try {
      $response = $client->request('GET', $url, ['auth' => [$this->jnjUser, $this->jnjPwd]]);
      return json_decode($response->getBody()->getContents());
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      return $e->getResponse()->getStatusCode();
    }
  }

  /**
   * Returns environment info.
   *
   * @param string $subscriptionName
   *   The subscription name.
   * @param string $environmentName
   *   The environment name.
   * @param string $domain
   *   The domain name to get env information for.
   *
   * @return \stdClass
   *   The environment info.
   */
  public function getEnvironmentInfo($subscriptionName, $environmentName, $domain = '')
  {
    $url = sprintf(
      '%s/api/subscriptions/%s/environments/%s',
      $this->dataproviderUrl,
      $subscriptionName,
      $environmentName
    );

    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $url, ['auth' => [$this->jnjUser, $this->jnjPwd]]);
    $body = json_decode($response->getBody()->getContents());

    // Unfortunately, we can't use an endpoint to get environment info for a
    // specific site. So, use this backway for now.
    if ($domain) {
      foreach ($body->site_definitions as $key => $definition) {
        foreach ($definition->locales as $locale_key => $locale) {
          foreach ($locale->domains as $info) {
            if ($info->url === $domain) {
              // Add a platform definition info.
              $body->site_definitions[$key]->platform_definition = $body->platform_definition;
              $body = $body->site_definitions[$key];
              break 2;
            }
          }
        }
      }
    }

    return $body;
  }
}
