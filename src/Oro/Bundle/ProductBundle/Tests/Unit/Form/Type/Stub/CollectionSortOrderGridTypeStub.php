<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\CollectionSortOrderGridType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CollectionSortOrderGridTypeStub extends CollectionSortOrderGridType
{
    public function __construct()
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['sortOrderConstraints'] = [
            'Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal' => [
                'payload' => null,
                'message' => 'This value should be decimal number.'
            ],
            "Range" => [
                'payload' => null,
                'notInRangeMessage' => 'This value should be between {{ min }} and {{ max }}.',
                'minMessage' => 'This value should be {{ limit }} or more.',
                'maxMessage' => 'This value should be {{ limit }} or less.',
                'invalidMessage' => 'This value should be a valid number.',
                'invalidDateTimeMessage' => 'This value should be a valid datetime.',
                'min' => 0,
                'minPropertyPath' => null,
                'max' => null,
                'maxPropertyPath' => null,
                'deprecatedMinMessageSet' => false,
                'deprecatedMaxMessageSet' => false
            ]
        ];
    }
}
