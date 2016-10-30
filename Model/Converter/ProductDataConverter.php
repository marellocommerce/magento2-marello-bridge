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
namespace Marello\Bridge\Model\Converter;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;

use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Helper\Config;

class ProductDataConverter implements DataConverterInterface
{
    const FQCN = Product::class;

    /** @var Config $helper */
    protected $helper;

    /** @var ResourceConnection $resource */
    protected $resource;

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

        $saleableOnlineData = $this->getExcludedOnline($entity);
        $data = array_merge($data, $saleableOnlineData);

        $saleableData = $this->getIsSaleable($entity);
        $data = array_merge($data, $saleableData);
        
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
                $websiteId = $this->helper->getWebsiteId($channelPrice->channel->code);

                $channelPrices[] = [
                    'website'   => $websiteId,
                    'price'     => $this->formatAmount($channelPrice->value),
                ];
            }
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
        if (!isset($entity->inventory) || !property_exists($entity, 'inventory')) {
            return [];
        }

        $inventory = 0;
        if (is_array($entity->inventory)) {
            foreach ($entity->inventory as $inventoryItem) {
                if (!property_exists($inventoryItem, 'currentLevel')) {
                    continue;
                }
                // for now only one warehouse is available, so we will not have multiple inventory items
                $inventory = $inventoryItem->currentLevel->stock;
            }
        }

        return ['qty' => $inventory];
    }

    /**
     * Get website ids based on sales channels
     * @return mixed
     */
    protected function getWebsitesIds($entity)
    {
        if (!isset($entity->channels) || !property_exists($entity, 'channels')) {
            return;
        }

        $websiteIds = [];
        $storeIds   = [];
        if (is_array($entity->channels)) {
            foreach ($entity->channels as $channel) {
                $websiteId = $this->helper->getWebsiteId($channel->code);
                $websiteIds[] = $websiteId;
                $storeIds[$websiteId] = $this->helper->getStoreIdsByWebsiteId($websiteId);
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
     * Check if we need to exclude product from site
     * @param $entity
     * @return array
     */
    protected function getExcludedOnline($entity)
    {
        if (!property_exists($entity, 'excluded_online')) {
            return [];
        }

        $saleable = true;
        if ($entity->excluded_online) {
            $saleable = false;
        }

        $storeIds   = [];
        if (is_array($entity->channels)) {
            foreach ($entity->channels as $channel) {
                if (!$channel->is_online_shop) {
                    continue;
                }

                $websiteId = $this->helper->getWebsiteId($channel->code);
                $storeIds[$websiteId] = $this->helper->getStoreIdsByWebsiteId($websiteId);
            }
        }

        return ['saleable_online'=> $saleable, 'saleable_stores' => $storeIds];
    }

    /**
     * Get visibility for product
     * @param $entity
     * @return array
     */
    protected function getVisibility($entity)
    {
        if (!property_exists($entity, 'saleable')) {
            return [];
        }

        $visibility = Visibility::VISIBILITY_IN_SEARCH;
        if ($entity->saleable) {
            $visibility = Visibility::VISIBILITY_BOTH;
        }

        return ['visibility' => $visibility];
    }

    /**
     * Get is saleable for product
     * @param $entity
     * @return array
     */
    protected function getIsSaleable($entity)
    {
        if (!property_exists($entity, 'saleable')) {
            return [];
        }

        return ['saleable' => $entity->saleable];
    }
}
