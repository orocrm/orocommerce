<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\SaleBundle\Form\Type\QuoteType;

class QuotePaymentTermAclExtension extends AbstractPaymentTermAclExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [QuoteType::class];
    }
}
