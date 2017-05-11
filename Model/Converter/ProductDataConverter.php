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
namespace Marello\Bridge\Model\Converter;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Helper\Config;

class ProductDataConverter implements DataConverterInterface
{

    const FQCN = Product::class;

    /** @var Config $helper */
    protected $helper;

    /**
     * ProductDataConverter constructor.
     * @param Config $helper
     */
    public function __construct(Config $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Convert product to Magento format
     * @param $entity
     * @return array
     */
    public function convertEntity($entity)
    {
        $data = [
            'sku' => $entity->sku,
            'name' => $entity->name,
        ];

        $websiteData = $this->getWebsitesIds($entity);
        $data = array_merge($data, $websiteData);

        $inventoryData = $this->getInventory($entity);
        $data = array_merge($data, $inventoryData);

        $priceData = $this->getPrices($entity, $websiteData);
        $data = array_merge($data, $priceData);

        $channelPriceData = $this->getChannelPrices($entity);
        if (!empty($channelPriceData['website_prices'])) {
            $data = array_merge($data, $channelPriceData);
        }

        $visibilityData = $this->getVisibility($entity);
        $data = array_merge($data, $visibilityData);

        $enabledData = $this->getStatus($entity);
        $data = array_merge($data, $enabledData);

        return $data;
    }

    /**
     * Convert amounts to float and round them on 4 decimals
     *
     * @param $amount
     * @return float
     */
    public function formatAmount($amount)
    {
        return round((float)$amount, 4);
    }

    /**
     * Get default prices for product
     * @param $entity
     * @param $websiteData
     * @return array
     */
    protected function getPrices($entity, $websiteData)
    {
        $prices = [];
        if (is_array($entity->prices) && !empty($entity->prices)) {
            foreach ($entity->prices as $defaultPrice) {
                $websiteIds = $this->helper->getWebsitesByCurrency($defaultPrice->currency);
                if (empty($websiteIds)) {
                    continue;
                }

                $websiteIds = array_intersect($websiteData['websites'], $websiteIds);
                // add default website
                if ($this->helper->getDefaultCurrency() === $defaultPrice->currency) {
                    $websiteIds[] = 0;
                }
                $prices[] = [
                    'websites'   => $websiteIds,
                    'price'     => $this->formatAmount($defaultPrice->value),
                ];
            }
        }

        if (count($prices) === 0) {
            return [];
        }

        return ['prices' => $prices];
    }

    /**
     * Get channel prices (website price) for product
     * @param $entity
     * @return array
     */
    protected function getChannelPrices($entity)
    {
        // first check if there are channel prices
        if (!isset($entity->channelPrices) || !property_exists($entity, 'channelPrices')) {
            return [];
        }
        $channelPrices = [];
        if (is_array($entity->channelPrices) && !empty($entity->channelPrices)) {
            foreach ($entity->channelPrices as $channelPrice) {
                // fix for the saleschannel code not being exposed..
                if (!property_exists($channelPrice->channel, 'code')) {
                    continue;
                }

                $websiteId = $this->helper->getWebsiteId($channelPrice->channel->code);
                if (!$websiteId) {
                    continue;
                }
                $channelPrices[] = [
                    'website'   => $websiteId,
                    'price'     => $this->formatAmount($channelPrice->value),
                ];
            }
        }

        if (count($channelPrices) === 0) {
            return [];
        }

        return ['website_prices' => $channelPrices];
    }

    /**
     * Get inventory levels for product
     * @param $entity
     * @return array
     */
    protected function getInventory($entity)
    {
        if (!isset($entity->inventoryItems) || !property_exists($entity, 'inventoryItems')) {
            return [];
        }

        $inventory = 0;
        if (is_array($entity->inventoryItems)) {
            foreach ($entity->inventoryItems as $inventoryItem) {
                if (!property_exists($inventoryItem, 'currentLevel')) {
                    continue;
                }

                if (is_object($inventoryItem->currentLevel)) {
                    // for now only one warehouse is available, so we will not have multiple inventory items
                    $inventory = $inventoryItem->currentLevel->inventory;
                }
            }
        }

        return ['qty' => $inventory];
    }

    /**
     * Get website ids based on sales channels
     * @param $entity
     * @return mixed
     */
    protected function getWebsitesIds($entity)
    {
        if (!isset($entity->channels) || !property_exists($entity, 'channels')) {
            return [];
        }

        $websiteIds = [];
        $storeIds   = [];
        if (is_array($entity->channels)) {
            foreach ($entity->channels as $channel) {
                $websiteId = $this->helper->getWebsiteId($channel->code);
                if ($websiteId !== 0) {
                    $websiteIds[] = $websiteId;
                    $storeIds[$websiteId] = $this->helper->getStoreIdsByWebsiteId($websiteId);
                }
            }
            // add default store
            $storeIds[0] = [0];
        }

        // add admin website
        if (count($websiteIds) === 0) {
            $websiteIds[] = 0;
        }

        return ['websites' => $websiteIds, 'stores' => $storeIds];
    }

    /**
     * Get visibility for product
     * @SKIL_SPECIFIC
     * @param $entity
     * @return array
     */
    protected function getVisibility($entity)
    {
        $visibility = Visibility::VISIBILITY_NOT_VISIBLE;

        // visibility based on status
        if (!property_exists($entity, 'status')) {
            return ['visibility' => $visibility];
        }

        if ($entity->status->name === 'enabled') {
            $visibility = Visibility::VISIBILITY_BOTH;
        }

        return ['visibility' => $visibility];
    }

    /**
     * Get is saleable for product
     * @param $entity
     * @return array
     */
    protected function getStatus($entity)
    {
        if (!property_exists($entity, 'status')) {
            return ['status' => Status::STATUS_DISABLED];
        }

        $status = Status::STATUS_DISABLED;

        if ($entity->status->name === 'enabled') {
            $status = Status::STATUS_ENABLED;
        }

        return ['status' => $status];
    }
}
