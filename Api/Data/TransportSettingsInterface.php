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
 * @package   Api
 * @copyright Copyright 2016 Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface TransportSettingsInterface extends ExtensibleDataInterface
{
    const XML_PATH_API_KEY      = 'marellobridgesettings/general/api_key';
    const XML_PATH_API_URL      = 'marellobridgesettings/general/api_url';
    const XML_PATH_API_USERNAME = 'marellobridgesettings/general/api_username';

    public function getApiKey();

    public function getApiUsername();

    public function getApiUrl();
}
