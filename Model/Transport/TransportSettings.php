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
namespace Marello\Bridge\Model\Transport;

use Magento\Framework\App\Config\ScopeConfigInterface;

use Marello\Bridge\Api\Data\TransportSettingsInterface;

class TransportSettings implements TransportSettingsInterface
{
    /** @var ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /**
     * TransportSettings constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Api key
     * @return string
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Get Api username
     * @return string
     */
    public function getApiUsername()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_USERNAME, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * Get Api url
     * @return string
     */
    public function getApiUrl()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
