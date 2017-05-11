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
namespace Marello\Bridge\Model\Writer\Attribute;

class PriceAttributeWriter extends AbstractAttributeWriter
{
    const ATTRIBUTE_NAME = 'price';

    /**
     * Save default product and website prices
     * @param $item
     */
    public function prepareAndSaveAttributeData($item)
    {
        if (empty($item['prices'])) {
            return;
        }

        $attributes = [];
        $attrId = $this->getAttributeId(self::ATTRIBUTE_NAME);
        $attrTable = $this->getAttributeTable(self::ATTRIBUTE_NAME);


        // prepare default price
        foreach ($item['prices'] as $price) {
            foreach ($price['websites'] as $website) {
                $storeIds = $item['stores'][(int)$website];
                foreach ($storeIds as $storeId) {
                    if (!isset($attributes[$attrTable][$item[$this->entityIdentifier]][$attrId][$storeId])) {
                        $attributes[$attrTable][$item[$this->entityIdentifier]][$attrId][$storeId] = $price['price'];
                    }
                }
            }
        }

        // channel prices from Marello
        if (!empty($item['website_prices'])) {
            // prepare channel price
            foreach ($item['website_prices'] as $price) {
                $storeIds = $item['stores'][(int)$price['website']];
                foreach ($storeIds as $storeId) {
                    $attributes[$attrTable][$item[$this->entityIdentifier]][$attrId][$storeId] = $price['price'];
                }
            }
        }

        // Save price attributes
        if (!empty($attributes)) {
            $this->saveAttribute($attrTable, $attributes[$attrTable]);
        }
    }
}
