<?php

namespace App\Model;

/**
 * Class SiteDir
 *
 * @package AppBundle\Model
 */
class SiteDir
{

    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var [] */
    private $links;

    /** @var string */
    private $location;

    /** @var string */
    private $id;

    /**
     * SiteDir constructor.
     *
     * @param       $id
     * @param       $name
     * @param       $version
     * @param array $links
     * @param       $location
     */
    public function __construct($id, $name, $version, array $links, $location)
    {
        $this->id = $id;
        $this->name = $name;
        $this->version = $version;
        $this->links = $links;
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
