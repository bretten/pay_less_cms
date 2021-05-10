<?php


namespace App\Services;


interface HostCreatorInterface
{
    /**
     * Creates a host for the specified site
     *
     * @param string $site The site to create a host for
     */
    public function createHost(string $site);

    /**
     * Creates a certificate for the site
     *
     * @param string $site The site to create a certificate for
     */
    public function createSiteCertificate(string $site);

    /**
     * Distributes the site if possible across content delivery networks
     *
     * @param string $site The site to distribute across CDN
     * @param array $data Supplemental data
     */
    public function distributeSite(string $site, array $data);
}
