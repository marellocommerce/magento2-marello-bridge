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

use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;

class WebsiteAttributeWriter extends AbstractAttributeWriter
{
    /**
     * @param $item
     * @return $this
     */
    public function prepareAndSaveAttributeData($item)
    {
        $tableName = $this->getProductWebsiteTable($this->getResource());

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
     * @param ResourceModel $resource
     * @return mixed
     */
    protected function getProductWebsiteTable($resource)
    {
        return $resource->getProductWebsiteTable();
    }
}
