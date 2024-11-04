<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'WEBSITE_ID';

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        $website = $this->websiteManager->getCurrentWebsite();

        if (!$website) {
            throw new \RuntimeException('Current website is not defined.');
        }

        return (string)$website->getId();
    }
}
