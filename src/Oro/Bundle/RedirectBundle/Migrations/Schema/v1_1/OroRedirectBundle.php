<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\FrontendBundle\Migration\UpdatePrefixQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable($schema, $queries, 'orob2b_redirect_slug', 'oro_redirect_slug');

        $queries->addQuery(new UpdatePrefixQuery('oro_redirect_slug', 'route_name'));
    }

    #[\Override]
    public function getOrder()
    {
        return 0;
    }
}
