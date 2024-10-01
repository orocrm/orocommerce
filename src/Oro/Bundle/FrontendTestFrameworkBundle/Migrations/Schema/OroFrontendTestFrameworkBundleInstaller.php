<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendTestFrameworkBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    SerializedFieldsExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;
    use SerializedFieldsExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createTestWebCatalog($schema);
        $this->createTestContentNode($schema);
        $this->createTestContentVariant($schema);

        $this->addVariantFieldToProduct($schema);
        $this->addWYSIWYGFieldToProduct($schema);
        $this->addWYSIWYGSerializedAttributeToProduct($schema);
    }

    private function createTestContentVariant(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_page_product', 'integer', ['notnull' => false]);
        $table->addColumn('category_page_category', 'integer', ['notnull' => false]);
        $table->addColumn('product_collection_segment', 'integer', ['notnull' => false]);
        $table->addColumn('node', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint('oro_product', ['product_page_product'], ['id']);
        $table->addForeignKeyConstraint('oro_catalog_category', ['category_page_category'], ['id']);
        $table->addForeignKeyConstraint(
            'oro_segment',
            ['product_collection_segment'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint('oro_test_content_node', ['node'], ['id'], ['onDelete' => 'CASCADE']);
    }

    private function createTestContentNode(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_content_node');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('web_catalog', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint('oro_test_web_catalog', ['web_catalog'], ['id']);
    }

    private function createTestWebCatalog(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_web_catalog');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    private function addVariantFieldToProduct(Schema $schema): void
    {
        if ($schema->hasTable('oro_product')) {
            $table = $schema->getTable('oro_product');

            $this->extendExtension->addEnumField(
                $schema,
                $table,
                'test_variant_field',
                'variant_field_code',
                false,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'entity' => ['label' => 'extend.entity.test.test_variant_field'],
                    'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                    'importexport' => ['excluded' => true]
                ]
            );

            $this->extendExtension->addEnumField(
                $schema,
                $table,
                'multienum_field',
                'multienum_code',
                true,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'entity' => ['label' => 'extend.entity.test.multienum_field'],
                    'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                    'importexport' => ['excluded' => true]
                ]
            );
        }
    }

    private function addWYSIWYGFieldToProduct(Schema $schema): void
    {
        if ($schema->hasTable('oro_product')) {
            $table = $schema->getTable('oro_product');
            $table->addColumn(
                'wysiwyg',
                'wysiwyg',
                [
                    'notnull' => false,
                    'comment' => '(DC2Type:wysiwyg)',
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_DEFAULT,
                        'attribute' => ['is_attribute' => true],
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                        'entity' => ['label' => 'extend.entity.test.wysiwyg'],
                        'dataaudit' => ['auditable' => false],
                        'importexport' => ['excluded' => false]
                    ]
                ]
            );
            $table->addColumn(
                'wysiwyg_style',
                'wysiwyg_style',
                [
                    'notnull' => false,
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                        'entity' => ['label' => 'extend.entity.test.wysiwyg_style'],
                        'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                        'dataaudit' => ['auditable' => false],
                        'importexport' => ['excluded' => false]
                    ]
                ]
            );
            $table->addColumn(
                'wysiwyg_properties',
                'wysiwyg_properties',
                [
                    'notnull' => false,
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                        'entity' => ['label' => 'extend.entity.test.wysiwyg_properties'],
                        'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                        'dataaudit' => ['auditable' => false],
                        'importexport' => ['excluded' => false]
                    ]
                ]
            );
        }
    }

    private function addWYSIWYGSerializedAttributeToProduct(Schema $schema): void
    {
        if (!$schema->hasTable('oro_product')) {
            return;
        }

        $table = $schema->getTable('oro_product');
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'wysiwygAttr',
            'wysiwyg',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_DEFAULT,
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test.wysiwyg_attr'],
                'attribute' => ['is_attribute' => true, 'field_name' => 'wysiwygAttr']
            ]
        );
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'wysiwygAttr_style',
            'wysiwyg_style',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test.wysiwyg_attr_style'],
                'attribute' => ['is_attribute' => true, 'field_name' => 'wysiwygAttr_style']
            ]
        );
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'wysiwygAttr_properties',
            'wysiwyg_properties',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'entity' => ['label' => 'extend.entity.test.wysiwyg_attr_properties'],
                'attribute' => ['is_attribute' => true, 'field_name' => 'wysiwygAttr_properties']
            ]
        );
    }
}
