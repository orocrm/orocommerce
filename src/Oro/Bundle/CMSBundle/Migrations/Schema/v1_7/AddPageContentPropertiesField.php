<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add content_properties field to the Page entity
 */
class AddPageContentPropertiesField implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_cms_page');
        if (!$table->hasColumn('content_properties')) {
            $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
        }
    }
}
