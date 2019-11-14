<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Environment;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Adds migrations required in functional tests.
 */
class TestEntitiesMigrationListener
{
    /**
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new AddAttributesToProductMigration());
    }
}
