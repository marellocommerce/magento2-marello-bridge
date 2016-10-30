<?php

/**
 * Marello
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is published at http://opensource.org/licenses/osl-3.0.php.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@marello.com so we can send you a copy immediately
 *
 * @category  Marello
 * @package   Bridge
 * @copyright Copyright 2016 Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->createMarelloEntityQueueTable($setup);
        }
        
        $setup->endSetup();
    }

    /**
     * @param $setup
     */
    protected function createMarelloEntityQueueTable($setup)
    {
        $tableName = $setup->getTable('marello_entity_queue');
        $table = $setup->getConnection()->newTable($tableName);
        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'nullable' => false,
                'primary' => true,
                'unsigned' => true
            ],
            'Queue ID'
        );
        $table->addColumn(
            'mag_id',
            Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false
            ],
            'Magento Entity ID'
        );
        $table->addColumn(
            'event_type',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ],
            'Event Type'
        );
        $table->addColumn(
            'entity_data',
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => false
            ],
            'Entity Data'
        );
        $table->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ],
            'Created At'
        );
        $table->addColumn(
            'processed_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => true,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ],
            'Processed At'
        );
        $table->addColumn(
            'processed',
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => true,
                'default' => true
            ],
            'Processed'
        );
        
        $indexName = $setup->getIdxName(
            'marello_queue_entity',
            ['mag_id', 'event_type'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $table->addIndex(
            $indexName,
            ['mag_id', 'event_type'],
            [
                'type' => AdapterInterface::INDEX_TYPE_UNIQUE
            ]
        );
        
        $setup->getConnection()->createTable($table);
    }
}
