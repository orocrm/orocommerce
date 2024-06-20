<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCMSBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;
    use SlugExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_15';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroCmsPageTable($schema);
        $this->createOroCmsPageSlugTable($schema);
        $this->createOroCmsPageSlugPrototypeTable($schema);
        $this->createOroCmsPageTitleTable($schema);
        $this->createOroCmsLoginPageTable($schema);
        $this->createOroCmsContentBlockTable($schema);
        $this->createOroCmsContentBlockTitleTable($schema);
        $this->createOroCmsContentBlockScopeTable($schema);
        $this->createOroCmsTextContentVariantTable($schema);
        $this->createOroCmsTextContentVariantScopeTable($schema);
        $this->createOroCmsContentWidgetTable($schema);
        $this->createOroCmsContentWidgetUsageTable($schema);
        $this->createOroCmsImageSlideTable($schema);
        $this->createTabbedContentItemTable($schema);
        $this->createOroCmsContentTemplateTable($schema);
        $this->addWysiwygEditorToContentTemplate($schema);
        $this->createOroCmsContentWidgetLabelTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageForeignKeys($schema);
        $this->addOroCmsPageTitleForeignKeys($schema);
        $this->addOroCmsContentBlockTitleForeignKeys($schema);
        $this->addOroCmsContentBlockScopeForeignKeys($schema);
        $this->addOrganizationForeignKeys($schema);
        $this->addOroCmsTextContentVariantForeignKeys($schema);
        $this->addOroCmsTextContentVariantScopeForeignKeys($schema);
        $this->addOroCmsContentWidgetForeignKeys($schema);
        $this->addOroCmsContentWidgetUsageForeignKeys($schema);
        $this->addOroCmsImageSlideForeignKeys($schema);
        $this->addTabbedContentItemForeignKeys($schema);
        $this->addForeignKeysToContentTemplate($schema);
        $this->addOroCmsContentWidgetLabelForeignKeys($schema);

        /** Associations */
        $this->addOroCmsLoginPageImageAssociations($schema);

        $this->addContentVariantTypes($schema);
        $this->addLocalizedFallbackValueFields($schema);
        $this->addContentBlockToSearchTermTable($schema);
        $this->addPageToSearchTermTable($schema);
    }

    private function createOroCmsContentTemplateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_template');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_content_template',
            'previewImage',
            ['attachment' => ['acl_protected' => true, 'use_dam' => false]],
            10
        );
    }

    private function addWysiwygEditorToContentTemplate(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_template');

        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn(
            'content_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
        $table->addColumn(
            'content_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
    }

    private function addForeignKeysToContentTemplate(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_template');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Create oro_cms_page table
     */
    private function createOroCmsPageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn(
            'content_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'draft' => ['draftable' => true],
                ],
            ]
        );
        $table->addColumn(
            'content_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'draft' => ['draftable' => true],
                ],
            ]
        );
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('draft_project_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_source_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_uuid', 'guid', ['notnull' => false]);
        $table->addColumn('draft_owner_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['draft_project_id'], 'IDX_BCE4CB4A2E26AC0B');
        $table->addIndex(['draft_source_id'], 'IDX_BCE4CB4A953C1C61');
        $table->addIndex(['draft_owner_id'], 'IDX_BCE4CB4ADCA3D9F3');
    }

    /**
     * Add oro_cms_page foreign keys.
     */
    private function addOroCmsPageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_draft_project'),
            ['draft_project_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['draft_source_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['draft_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_cms_login_page table
     */
    private function createOroCmsLoginPageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_login_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', ['notnull' => false]);
        $table->addColumn('bottom_content', 'text', ['notnull' => false]);
        $table->addColumn('css', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroCmsLoginPageImageAssociations(Schema $schema): void
    {
        $options['attachment']['acl_protected'] = false;
        $this->attachmentExtension->addImageRelation($schema, 'oro_cms_login_page', 'logoImage', $options, 10);
        $this->attachmentExtension->addImageRelation($schema, 'oro_cms_login_page', 'backgroundImage', $options, 10);
    }

    /**
     * Create oro_cms_page_slug table
     */
    private function createOroCmsPageSlugTable(Schema $schema): void
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_cms_page_to_slug',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_slug_prototype table
     */
    private function createOroCmsPageSlugPrototypeTable(Schema $schema): void
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_cms_page_slug_prototype',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_title table
     */
    private function createOroCmsPageTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_page_title');
        $table->addColumn('page_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['page_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_page_title foreign keys.
     */
    private function addOroCmsPageTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_page_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addContentVariantTypes(Schema $schema): void
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'cms_page',
                'oro_cms_page',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.cms.page.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['persist'],
                        'on_delete' => 'CASCADE',
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true]
                ]
            );
        }
    }

    /**
     * Create `oro_cms_content_block` table
     */
    private function createOroCmsContentBlockTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_block');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('alias', 'string', ['notnull' => true, 'length' => 100]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['alias']);
    }

    /**
     * Create `oro_cms_content_block_title` table
     */
    private function createOroCmsContentBlockTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_block_title');
        $table->addColumn('content_block_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['content_block_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create `oro_cms_content_block_scope` table
     */
    private function createOroCmsContentBlockScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_block_scope');
        $table->addColumn('content_block_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['content_block_id', 'scope_id']);
    }

    /**
     * Create oro_cms_content_widget table
     */
    private function createOroCmsContentWidgetTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('widget_type', 'string', ['length' => 255]);
        $table->addColumn('layout', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('settings', 'array');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['organization_id', 'name'], 'uidx_oro_cms_content_widget');
    }

    /**
     * Create oro_cms_content_widget_usage table
     */
    private function createOroCmsContentWidgetUsageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_usage');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('entity_field', 'string', ['notnull' => false, 'length' => 50]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['entity_class', 'entity_id', 'entity_field', 'content_widget_id'],
            'uidx_oro_cms_content_widget_usage'
        );
    }

    /**
     * Create oro_cms_image_slide table
     */
    private function createOroCmsImageSlideTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_image_slide');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('slide_order', 'integer', ['default' => 0]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('display_in_same_window', 'boolean', ['default' => true]);
        $table->addColumn('alt_image_text', 'string', ['length' => 255]);
        $table->addColumn('header', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->addColumn('text_alignment', 'string', ['length' => 20, 'default' => ImageSlide::TEXT_ALIGNMENT_CENTER]);

        $this->addSlideImageRelation($schema, 'extraLargeImage');
        $this->addSlideImageRelation($schema, 'extraLargeImage2x');
        $this->addSlideImageRelation($schema, 'extraLargeImage3x');
        $this->addSlideImageRelation($schema, 'largeImage');
        $this->addSlideImageRelation($schema, 'largeImage2x');
        $this->addSlideImageRelation($schema, 'largeImage3x');
        $this->addSlideImageRelation($schema, 'mediumImage');
        $this->addSlideImageRelation($schema, 'mediumImage2x');
        $this->addSlideImageRelation($schema, 'mediumImage3x');
        $this->addSlideImageRelation($schema, 'smallImage');
        $this->addSlideImageRelation($schema, 'smallImage2x');
        $this->addSlideImageRelation($schema, 'smallImage3x');

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cms_content_widget foreign keys.
     */
    private function addOroCmsContentWidgetForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_cms_content_widget_usage foreign keys.
     */
    private function addOroCmsContentWidgetUsageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget_usage');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_image_slide foreign keys.
     */
    private function addOroCmsImageSlideForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_image_slide');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add `oro_cms_content_block_title` foreign keys.
     */
    private function addOroCmsContentBlockTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_block_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add `oro_cms_content_block_scope` foreign keys.
     */
    private function addOroCmsContentBlockScopeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_block_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_content_block foreign keys.
     */
    private function addOrganizationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_block');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Create oro_cms_text_content_variant table
     */
    private function createOroCmsTextContentVariantTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_text_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_block_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_txt_cont_variant_scope table
     */
    private function createOroCmsTextContentVariantScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_txt_cont_variant_scope');
        $table->addColumn('variant_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['variant_id', 'scope_id']);
    }

    /**
     * Add oro_cms_text_content_variant foreign keys.
     */
    private function addOroCmsTextContentVariantForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_text_content_variant');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cms_txt_cont_variant_scope foreign keys.
     */
    private function addOroCmsTextContentVariantScopeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_txt_cont_variant_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_text_content_variant'),
            ['variant_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addLocalizedFallbackValueFields(Schema $schema): void
    {
        $table = $schema->getTable('oro_fallback_localization_val');
        $table->addColumn(
            'wysiwyg',
            'wysiwyg',
            [
                'notnull' => false,
                'comment' => '(DC2Type:wysiwyg)',
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => true],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
        $table->addColumn(
            'wysiwyg_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
        $table->addColumn(
            'wysiwyg_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
    }

    private function createTabbedContentItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_tabbed_content_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('item_order', 'integer', ['default' => 0]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn(
            'content_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
        $table->addColumn(
            'content_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    private function addTabbedContentItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_tabbed_content_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    public function addSlideImageRelation(Schema $schema, string $sourceColumnName): void
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            $sourceColumnName,
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            10
        );
    }

    /**
     * Create oro_cms_content_widget_label table
     */
    private function createOroCmsContentWidgetLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_label');
        $table->addColumn('content_widget_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['content_widget_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_content_widget_label foreign keys.
     */
    private function addOroCmsContentWidgetLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addContentBlockToSearchTermTable(Schema $schema): void
    {
        $owningSideTable = $schema->getTable('oro_website_search_search_term');
        $inverseSideTable = $schema->getTable('oro_cms_content_block');

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $owningSideTable,
            'contentBlock',
            $inverseSideTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'view' => ['is_displayable' => false],
                'form' => ['is_enabled' => false],
            ]
        );
    }

    private function addPageToSearchTermTable(Schema $schema): void
    {
        $owningSideTable = $schema->getTable('oro_website_search_search_term');
        $inverseSideTable = $schema->getTable('oro_cms_page');

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $owningSideTable,
            'redirectCmsPage',
            $inverseSideTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'view' => ['is_displayable' => false],
                'form' => ['is_enabled' => false],
            ]
        );
    }
}
