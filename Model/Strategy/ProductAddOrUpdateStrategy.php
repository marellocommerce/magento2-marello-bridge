<?php

namespace Marello\Bridge\Model\Strategy;

use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\Stdlib\DateTime;

use Marello\Bridge\Api\StrategyInterface;
use Marello\Bridge\Helper\EntityIdentifierHelper;

class ProductAddOrUpdateStrategy implements StrategyInterface
{
    const DEFAULT_PRODUCT_TYPE  = 'simple';
    const DEFAULT_ATTR_SET_ID   = 4;
    const DEFAULT_TAX_CLASS_ID  = 1;

    /** @var SkuProcessor $skuProcessor */
    protected $skuProcessor;

    /** @var EntityIdentifierHelper $entityIdentifierHelper */
    protected $entityIdentifierHelper;

    /** @var string $identifier */
    protected $identifier;

    /**
     * {@inheritdoc}
     * @param SkuProcessor $skuProcessor
     * @param EntityIdentifierHelper $entityIdentifierHelper
     */
    public function __construct(
        SkuProcessor $skuProcessor,
        EntityIdentifierHelper $entityIdentifierHelper
    ) {
        $this->skuProcessor             = $skuProcessor;
        $this->entityIdentifierHelper   = $entityIdentifierHelper;
    }

    /**
     * Process an item to be imported
     * @param $entity
     * @return mixed
     */
    public function process($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if ($existingEntity) {
            return $this->updateExistingEntityFields($existingEntity);
        } else {
            $entity = $this->createNewEntityData($entity);
        }

        return $entity;
    }


    /**
     * Update existing entity fields with identifiers
     * @param $existingEntity
     * @return mixed
     */
    protected function updateExistingEntityFields($existingEntity)
    {
        $entityIdentifier = $this->getIdentifier();

        $existingEntity[StrategyInterface::IS_NEW_KEY] = false;

        //backward compatibility for website && stockitem update
        $existingSkus = $this->getExistingSkus();
        $existingEntity['entity_id']  = $existingSkus[$existingEntity['sku']]['entity_id'];
        $existingEntity[$entityIdentifier] = $existingSkus[$existingEntity['sku']][$entityIdentifier];
        $existingEntity['tax_class_id'] = self::DEFAULT_TAX_CLASS_ID;
        // add/update updated_at field
        $existingEntity = $this->addUpdatedAtFieldToItem($existingEntity);

        return $existingEntity;
    }

    /**
     * Create a new product with the minimal of fields
     * @param $item
     * @return mixed
     */
    protected function createNewEntityData($item)
    {
        $item[StrategyInterface::IS_NEW_KEY] = true;
        $item['attribute_set_id'] = self::DEFAULT_ATTR_SET_ID;
        $item['type_id'] = self::DEFAULT_PRODUCT_TYPE;
        $item['tax_class_id'] = self::DEFAULT_TAX_CLASS_ID;
        $item['created_at'] = (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
        // add/update updated_at field
        $item = $this->addUpdatedAtFieldToItem($item);

        return $item;
    }

    /**
     * Try and find an existing entity in the old SKU's
     * @param $item
     * @return null
     */
    protected function findExistingEntity($item)
    {
        $existingSkus = $this->getExistingSkus();
        $existingEntity = null;

        if (isset($existingSkus[$item['sku']])) {
            $existingEntity = $item;
        }

        return $existingEntity;
    }

    /**
     * Add updated_at field to all items
     * @param $item
     * @return mixed
     */
    protected function addUpdatedAtFieldToItem($item)
    {
        $item['updated_at'] = (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
        return $item;
    }

    /**
     * Get entity identifier, Magento version =< 2.1.0 have a different one than > 2.1.0,
     * see EntityIdentifierHelper::getEntityIdentifier()
     * @return string
     */
    protected function getIdentifier()
    {
        if (!is_null($this->identifier)) {
            return $this->identifier;
        }

        $this->identifier = $this->entityIdentifierHelper->getEntityIdentifier();
        return $this->identifier;
    }

    /**
     * Get Existing SKU's in Magento
     * @return array
     */
    protected function getExistingSkus()
    {
        return $this->skuProcessor->getOldSkus();
    }
}
