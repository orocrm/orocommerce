<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundle implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOrob2BCmsPageTable($schema);
        $this->createOrob2BCmsPageToSlugTable($schema);
        $this->createOroCmsLoginPageTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BCmsPageForeignKeys($schema);
        $this->addOrob2BCmsPageToSlugForeignKeys($schema);

        $this->attachmentExtension->addImageRelation($schema, 'orob2b_cms_login_page', 'logoImage', [], 10);
        $this->attachmentExtension->addImageRelation($schema, 'orob2b_cms_login_page', 'backgroundImage', [], 10);
    }

    /**
     * Create orob2b_cms_page table
     */
    private function createOrob2BCmsPageTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('current_slug_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('content', 'text', []);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addUniqueIndex(['current_slug_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cms_page_to_slug table
     */
    private function createOrob2BCmsPageToSlugTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_cms_page_to_slug');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'slug_id']);
        $table->addUniqueIndex(['slug_id']);
    }

    /**
     * Add orob2b_cms_page foreign keys.
     */
    private function addOrob2BCmsPageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['current_slug_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cms_page_to_slug foreign keys.
     */
    private function addOrob2BCmsPageToSlugForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_cms_page_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_cms_login_page table
     */
    private function createOroCmsLoginPageTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_cms_login_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', ['notnull' => false]);
        $table->addColumn('bottom_content', 'text', ['notnull' => false]);
        $table->addColumn('css', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }
}
