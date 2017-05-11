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
namespace Marello\Bridge\Api\Data;

interface ProductAttributeWriterInterface
{
    /**
     * @param $item
     * @return mixed
     */
    public function prepareAndSaveAttributeData($item);

    /**
     * @param array $attributeData
     * @return mixed
     */
    public function saveAttributes(array $attributeData);

    /**
     * @param $attributeName
     * @return mixed
     */
    public function getAttribute($attributeName);
}
