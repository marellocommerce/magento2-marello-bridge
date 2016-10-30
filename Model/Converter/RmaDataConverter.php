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

use Magento\Rma\Model\ResourceModel\Item\CollectionFactory;

use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Helper\Config;

class RmaDataConverter implements DataConverterInterface
{
    /** @var Config $helper */
    protected $helper;

    /** @var CollectionFactory $returnItemCollectionFactory */
    protected $returnItemCollectionFactory;
    
    /**
     * ReturnDataConverter constructor.
     * @param Config $helper
     * @param CollectionFactory $returnItemCollectionFactory
     */
    public function __construct(
        Config $helper,
        CollectionFactory $returnItemCollectionFactory
    ) {
        $this->helper = $helper;
        $this->returnItemCollectionFactory = $returnItemCollectionFactory;
    }

    /**
     * Prepare a Magento RMA to fit the data structure
     * of Marello Return
     * @param $rma
     * @return array
     */
    public function convertEntity($rma)
    {
        $order = $rma->getOrder();
        $websiteId = $order->getStore()->getWebsite()->getId();
        $salesChannel = $this->helper->getChannelCode($websiteId);

        // basic return data
        $marelloData = json_decode(unserialize($order->getMarelloData()));
        $data = [
            'order'         => $marelloData->orderNumber,
            'salesChannel'  => $salesChannel,
            'returnReference' => $rma->getIncrementId()
        ];

        // return items
        $itemFactory = $this->returnItemCollectionFactory->create();
        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $lineItems = $itemFactory->addAttributeToSelect(
            '*'
        )->addFieldToFilter(
            'rma_entity_id',
            $rma->getEntityId()
        );
        
        $itemData = $this->prepareEntityLineItems($order, $lineItems);
        $data = array_merge($data, $itemData);

        return $data;
    }

    /**
     * Prepare a Magento rma line item to fit the data structure
     * of Marello ReturnItem
     * @param $items
     * @return array
     */
    public function prepareEntityLineItems($order, $items)
    {
        $itemData = [];
        foreach ($items as $item) {
            $orderItemId = $this->getMarelloOrderItemId($order, $item->getProductSku());
            $data['orderItem'] = $orderItemId;
            $data['quantity'] = $item->getQtyRequested();
            $data['reason'] = $this->getMarelloReturnReason($item);

            $itemData[] = $data;
        }

        return ['returnItems' => $itemData];
    }

    /**
     * Get the mapped return reason from backend
     * @param $item
     * @return string
     */
    protected function getMarelloReturnReason($item)
    {
        return $this->helper->getReturnReasonCode($item->getReason());
    }

    /**
     * Get Marello Order Item id from previous stored Marello data on Order
     * @param $order
     * @param $magentoSku
     * @return mixed
     */
    protected function getMarelloOrderItemId($order, $magentoSku)
    {
        if ($marelloData = $order->getMarelloData()) {
            $data = json_decode(unserialize($marelloData));
            foreach ($data->items as $item) {
                if ($item->productSku === $magentoSku) {
                    return $item->id;
                }
            }
        }
    }
}
