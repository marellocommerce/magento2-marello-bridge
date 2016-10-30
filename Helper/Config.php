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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Config extends AbstractHelper
{
    const XML_PATH_WEBSITE_CHANNELS     = 'marellobridgesettings/mapping/websites';
    const XML_PATH_RETURN_REASONS       = 'marellobridgesettings/mapping/return_reasons';
    const XML_PATH_IS_ENABLED           = 'marellobridgesettings/general/enabled';
    const XML_PATH_IS_TEST_MODE         = 'marellobridgesettings/general/test_mode';
    const XML_PATH_DEFAULT_CURRENCY     = 'currency/options/base';
    
    protected $channels;

    protected $returnReasons;

    /** @var StoreManagerInterface $storeManager */
    protected $storeManager;

    protected $websitesCache = null;

    protected $websiteIdToStoreIds = null;

    /**
     * Config constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Check if bridge in enabled
     * @return mixed
     */
    public function isBridgeEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IS_ENABLED,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Check if test mode is enabled
     * @return mixed
     */
    public function isTestModeEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IS_TEST_MODE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Retrieve channel code based on website id
     * @param $websiteId
     * @return null
     * @throws \Exception
     */
    public function getChannelCode($websiteId)
    {
        $channelMapping = $this->getChannels();
        $channelCode = null;
        if ($channelMapping) {
            $websites = unserialize($channelMapping);
            if (is_array($websites)) {
                foreach ($websites as $match) {
                    if ($websiteId === $match['website']) {
                        $channelCode = $match['saleschannel'];
                        break;
                    }
                }
            }
        }

        if (is_null($channelCode)) {
            throw new \Exception(sprintf('No channel configured for website id: %s', $websiteId));
        }
        
        return $channelCode;
    }

    /**
     * Retrieve website id with channel code
     *
     * @param string $channelCode
     *
     * @return string
     */
    public function getWebsiteId($channelCode)
    {
        $channelMapping = $this->getChannels();
        $websiteId = 0;
        if ($channelMapping) {
            $websites = unserialize($channelMapping);
            if (is_array($websites)) {
                foreach ($websites as $match) {
                    if ((string) $match['saleschannel'] === (string) $channelCode) {
                        $websiteId = (int) $match['website'];
                        break;
                    }
                }
            }
        }

        return $websiteId;
    }

    public function getDefaultCurrency()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_CURRENCY,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get all channel ids from default config
     * @return mixed
     */
    public function getChannels()
    {
        if (is_null($this->channels)) {
            $this->channels = $this->scopeConfig->getValue(
                self::XML_PATH_WEBSITE_CHANNELS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        
        return $this->channels;
    }

    /**
     * Get return reason based on the return reason option Id
     * @param $optionId
     * @return string
     */
    public function getReturnReasonCode($optionId)
    {
        $reasonMapping = $this->getReturnReasons();
        $reasonCode = 'other';
        if ($reasonMapping) {
            $reasons = unserialize($reasonMapping);
            if (is_array($reasons)) {
                foreach ($reasons as $match) {
                    if ((int) $match['magento_return_reason'] === (int) $optionId) {
                        $reasonCode = $match['marello_return_reason'];
                        break;
                    }
                }
            }
        }

        return $reasonCode;
    }
    
    /**
     * Get all return reasons codes from default config
     * @return mixed
     */
    public function getReturnReasons()
    {
        if (is_null($this->returnReasons)) {
            $this->returnReasons = $this->scopeConfig->getValue(
                self::XML_PATH_RETURN_REASONS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        return $this->returnReasons;
    }

    /**
     * Get websites based on the currency they're configured in
     * @param string $currency
     * @return array
     */
    public function getWebsitesByCurrency($currency = 'USD')
    {
        $websites   = $this->getWebsites();
        $websiteIds = [];
        foreach ($websites as $website) {
            if ($currency === $website->getBaseCurrencyCode()) {
                $websiteIds[] = $website->getId();
            }
        }

        return $websiteIds;
    }

    /**
     * Get stores for specific website
     * @param $websiteId
     * @return int
     */
    public function getStoreIdsByWebsiteId($websiteId)
    {
        $websites = $this->getWebsites();
        if (is_null($this->websiteIdToStoreIds)) {
            foreach ($websites as $website) {
                $this->websiteIdToStoreIds[$website->getId()] = array_flip($website->getStoreIds());
            }
        }
        
        if (isset($this->websiteIdToStoreIds[$websiteId])) {
            return $this->websiteIdToStoreIds[$websiteId];
        }

        // return default store id
        return 0;
    }

    /**
     * Get websites from store
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites()
    {
        if (is_null($this->websitesCache)) {
            $this->websitesCache = $this->storeManager->getWebsites();
        }

        return $this->websitesCache;
    }
}
