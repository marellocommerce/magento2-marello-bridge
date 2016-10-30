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
/**
 * System config source for getting websites from application
 */
namespace Marello\Bridge\Block\Adminhtml\System\Config\Source;

use Magento\Backend\Block\Template;

class Website extends Template
{
    /**
     * Get all available websites
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites()
    {
        // @codingStandardsIgnoreStart
        return $this->_storeManager->getWebsites();
        // @codingStandardsIgnoreEnd
    }

    /**
     * Convert website array to option array
     * @param array $data
     * @param bool $addEmpty
     * @return array
     */
    public function toOptionArray(array $data, $addEmpty = false)
    {
        $options = [];

        if ($addEmpty) {
            $options = ['value' => '', 'label' => __('-- Please Select Website --')];
        }

        foreach ($data as $item) {
            $options[] = ['value' => $item->getId(), 'label' => $item->getCode()];
        }

        return $options;
    }
}
