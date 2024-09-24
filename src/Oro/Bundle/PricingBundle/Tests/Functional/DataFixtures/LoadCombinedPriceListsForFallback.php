<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;

class LoadCombinedPriceListsForFallback extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t_4t_5t_6t',
            'enabled' => true,
            'calculated' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_1,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_2,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_4,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_5,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_6,
                    'mergeAllowed' => true,
                ],
            ]
        ],
        [
            'name' => '3t_4t_5t_6t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_4,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_5,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_6,
                    'mergeAllowed' => true,
                ]
            ]
        ],
        [
            'name' => '2t_3t_4t_6t',
            'enabled' => true,
            'calculated' => true,
            'blocked' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_2,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_4,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_6,
                    'mergeAllowed' => true,
                ]
            ]
        ],
        [
            'name' => '4t_5f_6t', // For Minimal prices
            'enabled' => true,
            'calculated' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_4,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_5,
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_6,
                    'mergeAllowed' => true,
                ]
            ]
        ],
        [
            'name' => '5t_6t', // For merge by priority
            'enabled' => true,
            'calculated' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_5,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_6,
                    'mergeAllowed' => true,
                ]
            ]
        ],
        [
            'name' => '1t_3t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_1,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ]
            ]
        ],
        [
            'name' => '3t',
            'enabled' => true,
            'calculated' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ]
            ]
        ]
    ];

    #[\Override]
    protected function createCombinedPriceList(array $priceListData, ObjectManager $manager): CombinedPriceList
    {
        $cpl = parent::createCombinedPriceList($priceListData, $manager);

        if (!empty($priceListData['blocked'])) {
            $manager->getRepository(CombinedPriceListBuildActivity::class)->addBuildActivities([$cpl], 10);
        }

        return $cpl;
    }

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadPriceLists::class
        ];
    }
}
