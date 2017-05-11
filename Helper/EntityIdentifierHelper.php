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
namespace Marello\Bridge\Helper;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;

class EntityIdentifierHelper
{
    /** @var MetadataPool $metadataPool */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        MetadataPool $metadataPool
    ) {
        $this->metadataPool = $metadataPool;
    }

    /**
     * Get Product Entity Identifier field
     * @return string
     */
    public function getEntityIdentifier()
    {
        return $this->getProductMetaData()->getIdentifierField();
    }

    /**
     * Get link field for product
     * @return string
     */
    public function getProductLinkField()
    {
        return $this->getProductMetaData()->getLinkField();
    }

    /**
     * @return EntityMetadataInterface
     */
    public function getProductMetaData()
    {
        return $this->metadataPool->getMetadata(ProductInterface::class);
    }
}
