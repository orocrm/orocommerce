<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPageTemplateToProductFields implements Migration, ExtendExtensionAwareInterface
{
    use AddFallbackRelationTrait;
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'pageTemplate',
            'oro.product.page_template.label',
            [
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_frontend.page_templates',
                ],
            ]
        );
    }
}
