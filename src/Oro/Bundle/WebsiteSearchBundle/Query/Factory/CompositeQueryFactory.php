<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class CompositeQueryFactory implements QueryFactoryInterface
{
    /** @var QueryFactoryInterface */
    protected $backendQueryFactory;

    /** @var QueryFactoryInterface */
    protected $websiteQueryFactory;

    public function __construct(
        QueryFactoryInterface $backendQueryFactory,
        QueryFactoryInterface $websiteQueryFactory
    ) {
        $this->backendQueryFactory = $backendQueryFactory;
        $this->websiteQueryFactory = $websiteQueryFactory;
    }

    #[\Override]
    public function create(array $config = [])
    {
        if (!isset($config['search_index']) || $config['search_index'] !== 'website') {
            return $this->backendQueryFactory->create($config);
        } else {
            return $this->websiteQueryFactory->create($config);
        }
    }
}
