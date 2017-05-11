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
namespace Marello\Bridge\Test\Unit\Model\Writer\Attribute;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item;
use Marello\Bridge\Model\Writer\Attribute\StockItemWriter;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockInterface;

class StockItemWriterTest extends \PHPUnit_Framework_TestCase
{
    /** @var StockItemWriter $stockItemWriter */
    protected $stockItemWriter;

    /** @var AdapterInterface $connection */
    protected $connection;

    /** @var StockConfigurationInterface $stockConfiguration */
    protected $stockConfiguration;

    /** @var StockRegistryInterface $stockRegistry */
    protected $stockRegistry;

    public function setUp()
    {
        $resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $resourceFactory = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resourceModelMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMainTable'])
            ->getMock();

        $this->stockConfiguration = $this->getMockBuilder(StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $resourceFactory->expects($this->any())
            ->method('create')
            ->willReturn($resourceModelMock);

        $resourceModelMock->expects($this->any())
            ->method('getMainTable')
            ->willReturn('cataloginventory_stock_item');

        $this->stockItemWriter = new StockItemWriter(
            $resourceConnection,
            $resourceFactory,
            $this->stockConfiguration,
            $this->stockRegistry
        );
    }

    /**
     * @test
     */
    public function itemProcessedHasQtyToSetStockToStockItem()
    {
        $item = [
            'entity_id' => 1,
            'website_id' => 2,
            'qty'   => 1
        ];

        $defaultScopeId = 1;

        $this->stockConfiguration->expects($this->exactly(1))
            ->method('getDefaultScopeId')
            ->willReturn($defaultScopeId);

        $stockItemMock = $this->getMockBuilder(StockInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockRegistry->expects($this->exactly(1))
            ->method('getStock')
            ->willReturn($stockItemMock);

        $stockItemMock->expects($this->exactly(1))
            ->method('getStockId')
            ->willReturn(1);

        $existingStockItemData = [
            'stock_id' => 1,
            'product_id'  => $item['entity_id'],
            'website_id' => $defaultScopeId,
            'qty'       => 0,
            'is_in_stock'   => 1,
            'manage_stock'  => 1
        ];

        $existingStockItemData['is_in_stock'] = ($item['qty'] > 0) ? 1 : 0;
        $existingStockItemData['qty'] = $item['qty'];
        $this->connection->expects($this->exactly(1))
            ->method('insertOnDuplicate')
            ->with('cataloginventory_stock_item', $existingStockItemData);

        $this->stockItemWriter->prepareAndSaveData($item);
    }



    /**
     * @test
     */
    public function itemProcessedHasNoQtyToUpdateARecord()
    {
        $item = [
            'entity_id' => 1,
            'website_id' => 2,
        ];

        $this->stockConfiguration->expects($this->never())
            ->method('getDefaultScopeId');

        $this->connection->expects($this->never())
            ->method('insertOnDuplicate');

        $this->stockItemWriter->prepareAndSaveData($item);
    }
}
