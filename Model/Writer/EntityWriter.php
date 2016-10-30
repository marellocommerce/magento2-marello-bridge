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
namespace Marello\Bridge\Model\Writer;

use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;

class EntityWriter
{
    const BATCH_SIZE = 25;

    const PRICE_ATTRIBUTE_CODE = 'price';

    const SKIL_WEBSITE_ONLY_ID = 9;
    
    const DEFAULT_TAX_CLASS_ID = 2;
    
    /**
     * Size of batch to delete attributes of products in one step.
     */
    const ATTRIBUTE_DELETE_SIZE = 1000;

    protected $logger;

    protected $skuProcessor;

    protected $resourceFactory;

    protected $connection;

    protected $stockResourceItemFactory;

    protected $stockConfiguration;

    protected $stockRegistry;

    protected $existingSkus = [];

    protected $priceAttributeCache;

    protected $resource;

    public function __construct(
        ResourceConnection $resource,
        ResourceModelFactory $resourceFactory,
        ItemFactory $stockResourceItemFactory,
        StockConfigurationInterface $stockConfiguration,
        StockRegistryInterface $stockRegistry,
        SkuProcessor $skuProcessor,
        LoggerInterface $logger
    ) {
        $this->connection               = $resource->getConnection();
        $this->resourceFactory          = $resourceFactory;
        $this->stockResourceItemFactory = $stockResourceItemFactory;
        $this->stockConfiguration       = $stockConfiguration;
        $this->stockRegistry            = $stockRegistry;
        $this->skuProcessor             = $skuProcessor;
        $this->logger                   = $logger;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function write(array $items)
    {
        // write
        $writeCount     = 0;
        $itemsToWrite   = [];

        foreach ($items as $item) {
            $processedItem = $this->getAdditionalData($item);
            if (null !== $processedItem) {
                $itemsToWrite[] = $processedItem;
                $writeCount++;
                if (0 === $writeCount % self::BATCH_SIZE) {
                    $this->writeItems($itemsToWrite);
                    $itemsToWrite = [];
                }
            }
        }

        if (count($itemsToWrite) > 0) {
            $this->writeItems($itemsToWrite);
        }
        return $this;
    }

    public function writeItems($items)
    {
        foreach ($items as $item) {
            $this->saveProductWebsites($item);
            $this->saveStockItem($item);
            $this->saveTaxClass($item);
            $this->saveEnabled($item);
            $this->saveCustomDesign($item);
            $this->saveVisibility($item);
            $this->saveIsSaleableOnline($item);
            $this->saveIsSaleable($item);
            $this->saveProductPrices($item);
        }
    }

    protected function getAdditionalData($item)
    {
        $this->existingSkus = $this->skuProcessor->getOldSkus();
        // 1. Entity phase
        if (!isset($this->existingSkus[$item['sku']])) {
            // mark updating 'available in store' to 'no'
            $this->logger->critical('No existing product found for sku ' . $item['sku']);
            return null;
        }

        // existing row
        $item['updated_at'] = (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
        $item['row_id']  = $this->existingSkus[$item['sku']]['row_id'];

        //backward compatibility for website && stockitem update
        $item['entity_id']  = $this->existingSkus[$item['sku']]['entity_id'];

        return $item;
    }

    /**
     * Save product websites.
     *
     * @param array $item
     * @return $this
     */
    protected function saveProductWebsites(array $item)
    {
        static $tableName = null;

        if (!$tableName) {
            $tableName = $this->resourceFactory->create()->getProductWebsiteTable();
        }

        if (isset($item['websites'])) {
            // format data
            $websitesData = [];
            foreach ($item['websites'] as $websiteId) {
                $websitesData[] = [
                    'product_id'    => $item['entity_id'],
                    'website_id'    => $websiteId
                ];
            }

            $where[] = $this->connection->quoteInto(
                '(website_id NOT IN (?)',
                array_values($item['websites'])
            ) . $this->connection->quoteInto(
                ' AND product_id = ?)',
                $item['entity_id']
            );

            if (!empty($where)) {
                $this->connection->delete($tableName, implode(' OR ', $where));
            }

            // $websitesData[] = ['product_id' => $productId, 'website_id' => $websiteId];
            if (!empty($websitesData)) {
                $this->connection->insertOnDuplicate($tableName, $websitesData);
            }
        }

        return $this;
    }

    /**
     * Stock item saving.
     *
     * @return $this
     */
    protected function saveStockItem($item)
    {
        /** @var $stockResource \Magento\CatalogInventory\Model\ResourceModel\Stock\Item */
        $stockResource = $this->stockResourceItemFactory->create();
        $entityTable = $stockResource->getMainTable();

        $row = [];
        $row['website_id'] = $this->stockConfiguration->getDefaultScopeId();
        $stockItem = $this->stockRegistry->getStockItem($item['entity_id'], $row['website_id']);
        $row = $stockItem->getData();
        $row['product_id'] = $item['entity_id'];
        $row['is_in_stock'] = ($item['qty'] > 0) ? 1 : 0;
        $row['qty'] = $item['qty'];

        // remove type id since it's not a column on the cataloginventory_stock_item table
        unset($row['type_id']);

        // Insert rows
        if (!empty($row)) {
            $this->connection->insertOnDuplicate($entityTable, $row);
        }

        return $this;
    }

    /**
     * TODO:: cleanup of to many foreach loops
     * @param $item
     */
    protected function saveProductPrices($item)
    {
        $attributes = [];
        $attribute = $this->getPriceAttribute();
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $storeIds = [0];

        // prepare default price
        foreach ($item['prices'] as $price) {
            foreach ($price['websites'] as $website) {
                $storeIds = $item['stores'][(int)$website];
                foreach ($storeIds as $storeId) {
                    if (!isset($attributes[$attrTable][$item['row_id']][$attrId][$storeId])) {
                        $attributes[$attrTable][$item['row_id']][$attrId][$storeId] = $price['price'];
                    }
                }
            }
        }

        if (!empty($item['website_prices'])) {
            // prepare channel price
            foreach ($item['website_prices'] as $price) {
                $storeIds = $item['stores'][(int)$price['website']];
                foreach ($storeIds as $storeId) {
                    $attributes[$attrTable][$item['row_id']][$attrId][$storeId] = $price['price'];
                }
            }
        }


        // Insert rows
        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    protected function saveIsSaleableOnline($item)
    {
        $attributes = [];
        $attribute = $this->getResource()->getAttribute('saleable_online');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $attributes[$attrTable][$item['row_id']][$attrId][0] = $item['saleable_online'];

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    protected function saveIsSaleable($item)
    {
        $attributes = [];
        $attribute = $this->getResource()->getAttribute('saleable');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $attributes[$attrTable][$item['row_id']][$attrId][0] = $item['saleable'];

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    /**
     * Set new design for specific products (products that are excluded from online sales)
     * @param $item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveCustomDesign($item)
    {
        if (!isset($item['saleable_stores']) || empty($item['saleable_stores'])) {
            return;
        }

        if ($item['saleable_online']) {
            return;
        }

        $attributes = [];
        $attribute = $this->getResource()->getAttribute('custom_design');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        foreach ($item['saleable_stores'] as $websiteId => $storeIds) {
            foreach ($storeIds as $storeId) {
                $attributes[$attrTable][$item['row_id']][$attrId][$storeId] = self::SKIL_WEBSITE_ONLY_ID;
            }
        }

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    protected function saveVisibility($item)
    {
        $attributes = [];
        $attribute =  $this->getResource()->getAttribute('visibility');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $attributes[$attrTable][$item['row_id']][$attrId][0] = $item['visibility'];

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    protected function saveEnabled($item)
    {
        $attributes = [];
        $attribute =  $this->getResource()->getAttribute('status');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $attributes[$attrTable][$item['row_id']][$attrId][0] = 1;

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }

    protected function saveTaxClass($item)
    {
        $attributes = [];
        $attribute =  $this->getResource()->getAttribute('tax_class_id');
        $attrId = $attribute->getId();
        $attrTable = $attribute->getBackend()->getTable();
        $attributes[$attrTable][$item['row_id']][$attrId][0] = self::DEFAULT_TAX_CLASS_ID;

        if (!empty($attributes)) {
            $this->saveAttribute($attributes);
        }
    }
    

    /**
     * TODO:: cleanup of to many foreach loops
     * Save product attributes.
     *
     * @param array $attributesData
     * @return $this
     */
    protected function saveAttribute(array $attributesData)
    {
        foreach ($attributesData as $tableName => $data) {
            $tableData = [];
            $where = [];
            foreach ($data as $productId => $attributes) {
                foreach ($attributes as $attributeId => $storeValues) {
                    foreach ($storeValues as $storeId => $storeValue) {
                        $tableData[] = [
                            'row_id' => $productId,
                            'attribute_id' => $attributeId,
                            'store_id' => $storeId,
                            'value' => $storeValue,
                        ];
                    }
                    /*
                    If the store based values are not provided for a particular store,
                    we default to the default scope values.
                    In this case, remove all the existing store based values stored in the table.
                    */
                    $where[] = $this->connection->quoteInto(
                        '(store_id NOT IN (?)',
                        array_keys($storeValues)
                    ) . $this->connection->quoteInto(
                        ' AND attribute_id = ?',
                        $attributeId
                    ) . $this->connection->quoteInto(
                        ' AND row_id = ?)',
                        $productId
                    );
                    if (count($where) >= self::ATTRIBUTE_DELETE_SIZE) {
                        $this->connection->delete($tableName, implode(' OR ', $where));
                        $where = [];
                    }
                }
            }
            if (!empty($where)) {
                $this->connection->delete($tableName, implode(' OR ', $where));
            }
            $this->connection->insertOnDuplicate($tableName, $tableData, ['value']);
        }
        return $this;
    }

    /**
     * Get price attribute
     *
     * @return mixed
     */
    public function getPriceAttribute()
    {
        if (is_null($this->priceAttributeCache)) {
            $this->priceAttributeCache = $this->getResource()->getAttribute(self::PRICE_ATTRIBUTE_CODE);
        }

        return $this->priceAttributeCache;
    }

    /**
     * @return \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel
     */
    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceFactory->create();
        }
        return $this->resource;
    }
}
