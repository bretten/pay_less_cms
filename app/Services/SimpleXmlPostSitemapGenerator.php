<?php


namespace App\Services;


use App\Contracts\Models\Post;
use App\Support\DateTimeFactoryInterface;
use SimpleXMLElement;

class SimpleXmlPostSitemapGenerator implements PostSitemapGenerator
{
    /**
     * @var string $protocol
     */
    private string $protocol;

    /**
     * @var DateTimeFactoryInterface $dateTimeFactory
     */
    private DateTimeFactoryInterface $dateTimeFactory;

    /**
     * Constructor
     *
     * @param string $protocol
     * @param DateTimeFactoryInterface $dateTimeFactory
     */
    public function __construct(string $protocol, DateTimeFactoryInterface $dateTimeFactory)
    {
        $this->protocol = $protocol;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * Generates a sitemap for the specified Posts
     *
     * @param Post[] $posts
     * @param string $site
     * @return string
     */
    public function generateSitemap(iterable $posts, string $site)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        $this->addUrlElement($xml, "$this->protocol://$site", $this->dateTimeFactory->getUtcNow()->format('Y-m-d'), 'daily', '1.0');
        $this->addUrlElement($xml, "$this->protocol://www.$site", $this->dateTimeFactory->getUtcNow()->format('Y-m-d'), 'daily', '1.0');

        foreach ($posts as $post) {

            if ($post->deletedAt) {
                continue;
            }

            $this->addUrlElement($xml, "$this->protocol://$post->site/$post->humanReadableUrl", $post->updatedAt->format('Y-m-d'), 'weekly', '1.0');
        }

        return $xml->asXML();
    }

    /**
     * Adds a url element to the XML
     *
     * @param SimpleXMLElement $xml
     * @param string $loc
     * @param string $lastMod
     * @param string $changeFreq
     * @param string $priority
     */
    private function addUrlElement(SimpleXMLElement $xml, string $loc, string $lastMod, string $changeFreq, string $priority)
    {
        $url = $xml->addChild('url');
        $url->addChild('loc', $loc);
        $url->addChild('lastmod', $lastMod);
        $url->addChild('changefreq', $changeFreq);
        $url->addChild('priority', $priority);
    }
}
