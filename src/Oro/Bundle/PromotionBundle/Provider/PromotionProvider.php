<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides information about promotions applicable to a specific source entity.
 */
class PromotionProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var RuleFiltrationServiceInterface
     */
    private $ruleFiltrationService;

    /**
     * @var ContextDataConverterInterface
     */
    private $contextDataConverter;

    /**
     * @var AppliedPromotionMapper
     */
    private $promotionMapper;

    /**
     * @var null|TokenAccessorInterface
     */
    private $tokenAccessor = null;

    /**
     * @var null|MemoryCacheProviderInterface
     */
    private $memoryCacheProvider = null;

    public function __construct(
        ManagerRegistry $registry,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        ContextDataConverterInterface $contextDataConverter,
        AppliedPromotionMapper $promotionMapper
    ) {
        $this->registry = $registry;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextDataConverter = $contextDataConverter;
        $this->promotionMapper = $promotionMapper;
    }

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor): void
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function setMemoryCacheProvider(MemoryCacheProviderInterface $memoryCacheProvider): void
    {
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    /**
     * @param object $sourceEntity
     * @return array|PromotionDataInterface[]
     */
    public function getPromotions($sourceEntity): array
    {
        $promotions = [];

        if ($sourceEntity instanceof AppliedPromotionsAwareInterface) {
            $promotions = $this->getAppliedPromotions($sourceEntity);
        }
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);
        $availablePromotions = $this->getAvailablePromotions($sourceEntity, $contextData);
        $promotions = array_merge($promotions, $availablePromotions);

        return $this->filterPromotions($contextData, $promotions);
    }

    /**
     * Checks whether promotion has been already applied to a given source entity.
     */
    public function isPromotionApplied($sourceEntity, PromotionDataInterface $promotion): bool
    {
        $promotions = $this->getPromotions($sourceEntity);

        foreach ($promotions as $appliedPromotion) {
            if ($appliedPromotion->getId() === $promotion->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether promotion can be applied to a given source entity.
     */
    public function isPromotionApplicable(
        $sourceEntity,
        PromotionDataInterface $promotion,
        array $skipFilters = []
    ): bool {
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        return !empty($this->filterPromotions($contextData, [$promotion], $skipFilters));
    }

    private function getAvailablePromotions(object $sourceEntity, array $contextData): array
    {
        $organization = $this->tokenAccessor ? $this->tokenAccessor->getOrganizationId() : null;
        if (null === $organization) {
            return [];
        }

        /** @var PromotionRepository $promotionRepository */
        $promotionRepository = $this->registry->getRepository(Promotion::class);
        $criteria = $contextData[ContextDataConverterInterface::CRITERIA] ?? null;
        $currentCurrency = $contextData[ContextDataConverterInterface::CURRENCY] ?? null;
        if ($this->memoryCacheProvider) {
            return $this->memoryCacheProvider->get(
                ['entity_hash' => spl_object_hash($sourceEntity)],
                fn () => $promotionRepository->getAvailablePromotions($criteria, $currentCurrency, $organization)
            );
        }

        return $promotionRepository->getAvailablePromotions($criteria, $currentCurrency, $organization);
    }

    private function filterPromotions(array $contextData, array $promotions, array $skipFilters = []): array
    {
        if (!empty($skipFilters)) {
            $contextData[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] = $skipFilters;
        }

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }

    private function getAppliedPromotions(AppliedPromotionsAwareInterface $sourceEntity): array
    {
        $appliedPromotions = [];
        foreach ($sourceEntity->getAppliedPromotions() as $appliedPromotionEntity) {
            if (!$appliedPromotionEntity->getPromotionData()) {
                continue;
            }
            $appliedPromotions[] = $this->promotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity);
        }

        return $appliedPromotions;
    }
}
