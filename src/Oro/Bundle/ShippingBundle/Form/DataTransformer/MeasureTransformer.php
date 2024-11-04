<?php

namespace Oro\Bundle\ShippingBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Symfony\Component\Form\DataTransformerInterface;

class MeasureTransformer implements DataTransformerInterface
{
    /** @var ObjectRepository */
    protected $repository;

    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    #[\Override]
    public function transform($values)
    {
        if (!is_array($values)) {
            return [];
        }

        $entities = [];
        foreach ($values as $value) {
            $entities[] = $this->repository->find($value);
        }

        return $entities;
    }

    #[\Override]
    public function reverseTransform($entities)
    {
        if (!is_array($entities)) {
            return [];
        }

        $values = [];
        foreach ($entities as $entity) {
            if ($entity instanceof MeasureUnitInterface) {
                $values[] = $entity->getCode();
            }
        }

        return $values;
    }
}
