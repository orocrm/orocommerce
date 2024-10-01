<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'orob2b_cms_page', 'oro_cms_page');
        $extension->renameTable($schema, $queries, 'orob2b_cms_page_to_slug', 'oro_cms_page_to_slug');
        $extension->renameTable($schema, $queries, 'orob2b_cms_login_page', 'oro_cms_login_page');
    }

    #[\Override]
    public function getOrder()
    {
        return 0;
    }
}
