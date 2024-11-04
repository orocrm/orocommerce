<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

class LoadPromotionDiscountData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadSegmentData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach ($this->getPromotionsData() as $reference => $promotionData) {
            $rule = new Rule();
            $rule->setName($promotionData['rule']['name']);
            $rule->setSortOrder($promotionData['rule']['sortOrder']);
            $rule->setEnabled($promotionData['rule']['enabled']);
            $rule->setStopProcessing($promotionData['rule']['stopFurtherRuleProcessing']);
            if (array_key_exists('expression', $promotionData['rule'])) {
                $rule->setExpression($promotionData['rule']['expression']);
            }

            $promotion = new Promotion();
            $promotion->setOwner($user);
            $promotion->setOrganization($user->getOrganization());
            $promotion->setRule($rule);
            $promotion->setUseCoupons(!empty($promotionData['useCoupons']) ? $promotionData['useCoupons'] : false);

            if (array_key_exists('schedules', $promotionData)) {
                foreach ($promotionData['schedules'] as $schedule) {
                    $schedule = new PromotionSchedule(
                        new \DateTime($schedule['activateAt'], new \DateTimeZone('UTC')),
                        new \DateTime($schedule['deactivateAt'], new \DateTimeZone('UTC'))
                    );
                    $promotion->addSchedule($schedule);
                }
            }

            $discountConfiguration = new DiscountConfiguration();
            $discountConfiguration->setType($promotionData['discountConfiguration']['type']);
            $discountConfiguration->setOptions($promotionData['discountConfiguration']['options']);

            /** @var Segment $segment */
            $segment = $this->getReference($promotionData['segmentReference']);

            $promotion->setDiscountConfiguration($discountConfiguration);
            $promotion->setProductsSegment($segment);

            if (array_key_exists('scopeCriterias', $promotionData)) {
                foreach ($promotionData['scopeCriterias'] as $scopeCriteria) {
                    $scopeCriteria = $this->getScope($scopeCriteria);
                    $promotion->addScope($scopeCriteria);
                }
            }

            $manager->persist($promotion);
            $this->setReference($reference, $promotion);
        }

        $manager->flush();
    }

    private function getScope(array $scopeCriteria): Scope
    {
        return $this->container->get('oro_scope.scope_manager')->findOrCreate('promotion', $scopeCriteria);
    }

    private function getPromotionsData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/promotions.yml'));
    }
}
