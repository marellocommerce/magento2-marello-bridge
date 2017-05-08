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

use Psr\Log\LoggerInterface;

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Staging\Model\VersionManager;

use Marello\Bridge\Api\StrategyInterface;
use Marello\Bridge\Helper\EntityIdentifierHelper;
use Marello\Bridge\Model\Writer\Attribute\DefaultAttributeWriter;
use Marello\Bridge\Model\Writer\Attribute\WebsiteAttributeWriter;
use Marello\Bridge\Model\Writer\Attribute\PriceAttributeWriter;
use Marello\Bridge\Model\Writer\Attribute\StockItemWriter;

class EntityWriter
{
    const BATCH_SIZE = 25;

    // @SKIL_SPECIFIC
    const SKIL_WEBSITE_ONLY_ID = 9;

    /** @var LoggerInterface $logger */
    protected $logger;

    /** @var PriceAttributeWriter $priceAttributeWriter */
    protected $priceAttributeWriter;

    /** @var DefaultAttributeWriter $defaultAttributeWriter */
    protected $defaultAttributeWriter;

    /** @var WebsiteAttributeWriter $websiteAttributeWriter */
    protected $websiteAttributeWriter;

    /** @var $resource */
    protected $resource;

    /** @var ResourceModelFactory $resourceFactory */
    protected $resourceFactory;

    /** @var AdapterInterface $connection */
    protected $connection;

    /** @var AbstractAttribute $attributeCache */
    protected $attributeCache;

    /** @var string */
    protected $entityIdentifier;

    /** @var EntityIdentifierHelper $entityIdentifierHelper */
    protected $entityIdentifierHelper;

    /** @var IndexerRegistry $indexerRegistry */
    protected $indexerRegistry;

    /** @var MetadataPool $metadataPool */
    private $metadataPool;

    public function __construct(
        ResourceConnection $resourceConnection,
        ResourceModelFactory $resourceFactory,
        EntityIdentifierHelper $entityIdentifierHelper,
        DefaultAttributeWriter $defaultAttributeWriter,
        WebsiteAttributeWriter $websiteAttributeWriter,
        PriceAttributeWriter $priceAttributeWriter,
        StockItemWriter $stockItemWriter,
        IndexerRegistry $indexerRegistry,
        LoggerInterface $logger,
        MetadataPool $metadataPool
    ) {
        $this->defaultAttributeWriter   = $defaultAttributeWriter;
        $this->websiteAttributeWriter   = $websiteAttributeWriter;
        $this->priceAttributeWriter     = $priceAttributeWriter;
        $this->stockItemWriter          = $stockItemWriter;
        $this->logger                   = $logger;
        $this->connection               = $resourceConnection->getConnection();
        $this->resourceFactory          = $resourceFactory;
        $this->resource                 = $resourceFactory->create();
        $this->entityIdentifierHelper   = $entityIdentifierHelper;
        $this->entityIdentifier         = $entityIdentifierHelper->getEntityIdentifier();
        $this->indexerRegistry          = $indexerRegistry;
        $this->metadataPool             = $metadataPool;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function write(array $items)
    {
        $productIds = [];
        foreach ($items as $item) {
            $item = $this->saveProductEntity($item);
            $this->saveDefaultAttributes($item);
            $this->saveProductWebsites($item);
            $this->saveProductPrices($item);
            $this->saveStockItem($item);
            $productIds[] = $item[$this->entityIdentifier];
        }

        $this->reindexRecords($productIds);

        return $this;
    }

    /**
     * Save product websites.
     *
     * @param array $item
     * @return $this
     */
    protected function saveProductWebsites(array $item)
    {
        $this->websiteAttributeWriter->prepareAndSaveAttributeData($item);
    }

    /**
     * Stock item saving.
     *
     * @return $this
     */
    protected function saveStockItem($item)
    {
        $this->stockItemWriter->prepareAndSaveData($item);
    }

    /**
     * Update and insert data in entity table.
     *
     * @param array $item item for insert or update
     * @return array $item
     * @throws \Exception
     */
    protected function saveProductEntity($item)
    {
        static $entityTable = null;

        if (!$entityTable) {
            $entityTable = $this->getResource()->getEntityTable();
        }

        if (!$item[StrategyInterface::IS_NEW_KEY]) {
            $data[$this->entityIdentifier]  = $item[$this->entityIdentifier];
            $data['updated_at'] = $item['updated_at'];
            try {
                $this->connection->insertOnDuplicate($entityTable, $data, ['updated_at']);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

            // unset status for skil's existing products
            unset($item['status']);
        } else {
            $data = [
                'attribute_set_id'  => $item['attribute_set_id'],
                'type_id'           => $item['type_id'],
                'sku'               => $item['sku'],
                'created_at'        => $item['created_at'],
                'updated_at'        => $item['updated_at']
            ];

            $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $data[$metadata->getIdentifierField()] = $metadata->generateIdentifier();
            // EE only...
//            $data['created_in'] = 1;
//            $data['updated_in'] = VersionManager::MAX_VERSION;

            try {
                $this->connection->insertMultiple($entityTable, $data);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

            $newProduct = $this->connection->fetchRow(
                $this->connection->select()->from(
                    $entityTable,
                    ['sku', 'entity_id', $this->entityIdentifier]
                )->where(
                    'sku IN (?)',
                    $item['sku']
                )
            );

            $item['entity_id'] = $newProduct['entity_id'];
            $item[$this->entityIdentifier] = $newProduct[$this->entityIdentifier];
        }

        unset($item[StrategyInterface::IS_NEW_KEY]);

        return $item;
    }

    /**
     * {@inheritdoc}
     * @param $item
     */
    protected function saveProductPrices($item)
    {
        $this->priceAttributeWriter->prepareAndSaveAttributeData($item);
    }

    /**
     * {@inheritdoc}
     * @param $item
     */
    protected function saveDefaultAttributes($item)
    {
        $this->defaultAttributeWriter->prepareAndSaveAttributeData($item);
    }

    /**
     * {@inheritdoc}
     * @return \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel
     */
    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceFactory->create();
        }
        return $this->resource;
    }

    protected function reindexRecords($productIds)
    {
        $indexer = $this->indexerRegistry->get('catalog_product_category');
        $indexer->reindexList($productIds);
    }
}
