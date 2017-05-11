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
 * @copyright Copyright Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Setup\Module\Dependency\Report\Circular\Data\Module;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * upgrade data
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if ($context->getVersion()
            && version_compare($context->getVersion(), '1.0.1', '<')
        ) {
            $this->upgrade101($setup);
        }

        if ($context->getVersion()
            && version_compare($context->getVersion(), '1.1.1', '<')
        ) {
            $this->upgrade111($setup);
        }

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     * @param ModuleDataSetupInterface $setup
     */
    public function upgrade101(ModuleDataSetupInterface $setup)
    {
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'marello_export_status',
            [
                'type' => Table::TYPE_TEXT,
                'comment' => 'Marello Export Status'
            ]
        );

        $installer->endSetup();
    }

    /**
     * {@inheritdoc}
     * @param ModuleDataSetupInterface $setup
     */
    public function upgrade111(ModuleDataSetupInterface $setup)
    {
        $installer = $setup;
        $installer->startSetup();
        $columnExists = $installer->getConnection()->tableColumnExists('sales_order_grid', 'marello_export_status');
        if (!$columnExists) {
            $this->upgrade101($setup);
        }

        $installer->endSetup();
    }
}
