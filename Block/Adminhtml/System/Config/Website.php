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
/**
 * System config element for rendering websites in backend
 */
namespace Marello\Bridge\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;

use Marello\Bridge\Block\Adminhtml\System\Config\Source\Website as SourceWebsite;

class Website extends AbstractFieldArray
{
    const COLUMN_NAME_WEBSITE       = 'website';
    const COLUMN_NAME_SALESCHANNEL  = 'saleschannel';

    /** @var SourceWebsite $source */
    protected $source;
    
    /** @var Factory $elementFactory */
    protected $elementFactory;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param SourceWebsite $website
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        SourceWebsite $website,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->source = $website;
        parent::__construct($context, $data);
    }


    // @codingStandardsIgnoreStart
    /**
     * initialise
     */
    protected function _construct()
    {
        $this->addColumn(
            self::COLUMN_NAME_SALESCHANNEL,
            array(
                'label' => __('Sales Channel code'),
                'style' => 'width:120px',
            )
        );

        $this->addColumn(
            self::COLUMN_NAME_WEBSITE,
            array(
                'renderer' => $this->source,
                'label'    => __('Website'),
                'style'    => 'width:120px',
            )
        );

        $this->_addAfter = false;

        parent::_construct();
    }
    // @codingStandardsIgnoreEnd


    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName === self::COLUMN_NAME_WEBSITE && isset($this->_columns[$columnName])) {
            $options = $this->getWebsiteOptions();
            $element = $this->elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get array with website options
     * @return array
     */
    protected function getWebsiteOptions()
    {
        $websites = $this->source->getWebsites();
        return $this->source->toOptionArray($websites, true);
    }
}
