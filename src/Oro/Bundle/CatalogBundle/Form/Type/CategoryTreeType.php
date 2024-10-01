<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type provides functionality to select an existing category from the tree
 */
class CategoryTreeType extends AbstractType
{
    const NAME = 'oro_catalog_category_tree';

    /**
     * @var AbstractTreeHandler
     */
    private $treeHandler;

    public function __construct(AbstractTreeHandler $treeHandler)
    {
        $this->treeHandler = $treeHandler;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'tree_key' => 'commerce-category',
            'tree_data' => [$this->treeHandler, 'createTreeByMasterCatalogRoot']
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityTreeSelectType::class;
    }
}
