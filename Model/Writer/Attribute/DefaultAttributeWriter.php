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

class DefaultAttributeWriter extends AbstractAttributeWriter
{
    const DEFAULT_TAX_CLASS_ID = 1;

    protected static $defaultAttributes = [
        'name',
        'visibility',
        'status',
        'tax_class_id',
    ];

    /**
     * Save default product and website prices
     * @param $item
     */
    public function prepareAndSaveAttributeData($item)
    {
        $attributes = [];
        foreach (self::$defaultAttributes as $attributeName) {
            if (!isset($item[$attributeName])) {
                continue;
            }
            $attrId = $this->getAttributeId($attributeName);
            $attrTable = $this->getAttributeTable($attributeName);
            $attributes[$attrTable][$item[$this->entityIdentifier]][$attrId][0] = $item[$attributeName];
        }

        if (!empty($attributes)) {
            $this->saveAttributes($attributes);
        }
    }
}
