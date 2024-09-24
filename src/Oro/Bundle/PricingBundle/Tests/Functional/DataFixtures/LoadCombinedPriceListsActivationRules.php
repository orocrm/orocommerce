<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

class LoadCombinedPriceListsActivationRules extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'fullCombinedPriceList' => '2f_1t_3t',
            'combinedPriceList' => '2f',
            'activateAtOffset' => '+12 hours',
            'expiredAtOffset' => '+24 hours',
            'active' => false
        ],
        [
            'fullCombinedPriceList' => '2f_1t_3t',
            'combinedPriceList' => '2f',
            'activateAtOffset' => '+2 days',
            'expiredAtOffset' => '+3 days',
            'active' => false,
        ],
        [
            'fullCombinedPriceList' => '1f',
            'combinedPriceList' => '2f',
            'activateAtOffset' => null,
            'expiredAtOffset' => '+5 days',
            'active' => false,
        ],
        [
            'fullCombinedPriceList' => '1f',
            'combinedPriceList' => '1f',
            'activateAtOffset' => '+6 days',
            'expiredAtOffset' => null,
            'active' => false,
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $priceLisRuleData) {
            $combinedPriceListRule = new CombinedPriceListActivationRule();

            /** @var CombinedPriceList $fullCombinedPriceList */
            $fullCombinedPriceList = $this->getReference($priceLisRuleData['fullCombinedPriceList']);
            $combinedPriceListRule->setFullChainPriceList($fullCombinedPriceList);

            /** @var CombinedPriceList $combinedPriceList */
            $combinedPriceList = $this->getReference($priceLisRuleData['combinedPriceList']);
            $combinedPriceListRule->setCombinedPriceList($combinedPriceList);

            $combinedPriceListRule->setActive($priceLisRuleData['active']);
            if ($priceLisRuleData['activateAtOffset']) {
                $combinedPriceListRule->setActivateAt((new \DateTime('now', new \DateTimeZone('UTC')))
                    ->modify($priceLisRuleData['activateAtOffset']));
            }
            if ($priceLisRuleData['expiredAtOffset']) {
                $combinedPriceListRule->setExpireAt((new \DateTime('now', new \DateTimeZone('UTC')))
                    ->modify($priceLisRuleData['expiredAtOffset']));
            }

            $manager->persist($combinedPriceListRule);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadCombinedPriceLists::class
        ];
    }
}
