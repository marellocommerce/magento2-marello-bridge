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
namespace Marello\Bridge\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ReturnReason extends AbstractFieldArray
{
    // @codingStandardsIgnoreStart
    /**
     * initialise
     */
    protected function _construct()
    {
        $this->addColumn(
            'marello_return_reason',
            array(
                'label'    => __('Marello Return Reason Option code'),
                'style'    => 'width:230px',
            )
        );

        $this->addColumn(
            'magento_return_reason',
            array(
                'label' => __('Magento Return Reason Option id'),
                'style' => 'width:230px',
            )
        );

        $this->_addAfter = false;

        parent::_construct();
    }
    // @codingStandardsIgnoreEnd
}
