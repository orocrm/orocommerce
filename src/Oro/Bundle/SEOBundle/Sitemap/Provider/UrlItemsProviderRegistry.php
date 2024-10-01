<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

class UrlItemsProviderRegistry implements UrlItemsProviderRegistryInterface
{
    /**
     * @var array|UrlItemsProviderInterface[]
     */
    private $providers = [];

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    #[\Override]
    public function getProvidersIndexedByNames()
    {
        return $this->providers;
    }

    #[\Override]
    public function getProviderByName($name)
    {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
}
