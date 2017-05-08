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
namespace Marello\Bridge\Model\Writer\Attribute;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class StockItemWriter
{
    /** @var AdapterInterface $connection */
    protected $connection;

    /** @var ItemFactory $resourceFactory */
    protected $resourceFactory;

    /** @var Item $resource */
    protected $resource;

    /** @var StockConfigurationInterface $stockConfiguration */
    protected $stockConfiguration;

    /** @var StockRegistryInterface $stockRegistry */
    protected $stockRegistry;

    public function __construct(
        ResourceConnection $resourceConnection,
        ItemFactory $resourceFactory,
        StockConfigurationInterface $stockConfiguration,
        StockRegistryInterface $stockRegistry
    ) {
        $this->connection               = $resourceConnection->getConnection();
        $this->resourceFactory          = $resourceFactory;
        $this->resource                 = $resourceFactory->create();
        $this->stockConfiguration       = $stockConfiguration;
        $this->stockRegistry            = $stockRegistry;
    }

    /**
     * @param $item
     * @return $this
     */
    public function prepareAndSaveData($item)
    {
        if (!isset($item['qty'])) {
            return $this;
        }

        $stockItemTable = $this->getStockItemTable($this->resource);
        $stockItemRecord['website_id'] = $this->getScopeId($this->stockConfiguration);
        $stockItemRecord['product_id'] = $item['entity_id'];
        $stockItemRecord['is_in_stock'] = ($item['qty'] > 0) ? 1 : 0;
        $stockItemRecord['manage_stock'] = 1;
        $stockItemRecord['qty'] = $item['qty'];
        $stockItemRecord['stock_id'] = $this->stockRegistry->getStock($stockItemRecord['website_id'])->getStockId();


        // Insert rows
        if (!empty($stockItemRecord)) {
            try {
                $this->connection->insertOnDuplicate($stockItemTable, $stockItemRecord);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                die(__METHOD__);
            }
        }

        return $this;
    }

    /**
     * @param Item $resource
     * @return mixed
     */
    protected function getStockItemTable($resource)
    {
        return $resource->getMainTable();
    }

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @return mixed
     */
    protected function getScopeId($stockConfiguration)
    {
        return $stockConfiguration->getDefaultScopeId();
    }
}
