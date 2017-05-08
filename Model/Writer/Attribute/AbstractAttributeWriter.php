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

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DB\Adapter\AdapterInterface;

use Marello\Bridge\Api\Data\ProductAttributeWriterInterface;
use Marello\Bridge\Helper\EntityIdentifierHelper;

abstract class AbstractAttributeWriter implements ProductAttributeWriterInterface
{
    const ATTRIBUTE_DELETE_SIZE = 1000;

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

    public function __construct(
        ResourceConnection $resourceConnection,
        ResourceModelFactory $resourceFactory,
        EntityIdentifierHelper $entityIdentifierHelper
    ) {
        $this->connection               = $resourceConnection->getConnection();
        $this->resourceFactory          = $resourceFactory;
        $this->resource                 = $resourceFactory->create();
        $this->entityIdentifierHelper   = $entityIdentifierHelper;
        $this->entityIdentifier         = $entityIdentifierHelper->getEntityIdentifier();
    }

    /**
     * Save product attributes.
     *
     * @param array $attributesData
     * @return $this
     */
    public function saveAttributes(array $attributesData)
    {
        foreach ($attributesData as $tableName => $data) {
            $this->saveAttribute($tableName, $data);
        }
    }

    /**
     * {@inheritdoc}
     * @param $tableName
     * @param $data
     * @return $this
     */
    protected function saveAttribute($tableName, $data)
    {
        $tableData = [];
        $where = [];
        foreach ($data as $productId => $attributes) {
            foreach ($attributes as $attributeId => $storeValues) {
                foreach ($storeValues as $storeId => $storeValue) {
                    $tableData[] = [
                        $this->entityIdentifier => $productId,
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
                    sprintf(' AND %s = ?)', $this->entityIdentifier),
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

        return $this;
    }

    /**
     * Get attribute
     *
     * @param $attributeName
     * @return mixed
     */
    public function getAttribute($attributeName)
    {
        if (is_null($this->attributeCache) || ($this->attributeCache->getName() !== $attributeName)) {
            $this->attributeCache = $this->getResource()->getAttribute($attributeName);
        }

        return $this->attributeCache;
    }

    /**
     * Get attribute id by attributename
     * @param $attributeName
     * @return mixed
     */
    protected function getAttributeId($attributeName)
    {
        return $this->getAttribute($attributeName)->getId();
    }

    /**
     * Get attribute table by name
     * @param $attributeName
     * @return string
     */
    protected function getAttributeTable($attributeName)
    {
        return $this->getAttribute($attributeName)->getBackendTable();
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
