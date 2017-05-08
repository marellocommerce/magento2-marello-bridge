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
namespace Marello\Bridge\Helper;

use Magento\Framework\App\ProductMetadataInterface;

class EntityIdentifierHelper
{
    const AFFECTED_MAGENTO_VERSION = '2.1.0';

    /** @var ProductMetadataInterface $productMetadata */
    protected $productMetaData;

    /**
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetaData = $productMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        $identifier = 'entity_id';
        $version = $this->getMagentoVersion();
        if (version_compare($version, self::AFFECTED_MAGENTO_VERSION, '>')) {
            $identifier = 'row_id';
        }

        return $identifier;
    }

    /**
     * Get magento version
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetaData->getVersion();
    }
}
