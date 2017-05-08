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
namespace Marello\Bridge\Model\Processor;

use Magento\Sales\Api\OrderRepositoryInterface;

use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Model\Transport\MarelloTransportInterface;
use Marello\Bridge\Model\Converter\OrderDataConverter;

class NewOrderProcessor extends AbstractProcessor
{
    /** @var OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var array|mixed $customers */
    protected $customers = null;

    /**
     * NewOrderProcessor constructor.
     * @param ConnectorRegistryInterface $connectorRegistry
     * @param DataConverterRegistryInterface $converterRegistry
     * @param MarelloTransportInterface $transport
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        MarelloTransportInterface $transport,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository  = $orderRepository;
        parent::__construct($connectorRegistry, $converterRegistry, $transport);
    }

    /**
     * Process new orders
     * @param array $orderData
     * @return $this
     */
    public function process(array $orderData)
    {
        $order = $orderData['order'];
        // check if customer exists
        // apparently we can only get a full list of customers -.-'
        $existingCustomer = $this->findExistingCustomer($order->getCustomerEmail());

        if (null === $existingCustomer) {
            $this->processCustomer($order);
            $existingCustomer = $this->findExistingCustomer($order->getCustomerEmail());
        }
        
        if (!$order->getData('marello_data')) {
            $result = $this->syncOrder($order, $existingCustomer);
            if (strpos($result, 'The requested URL') === false) {
                $this->saveOrderData($result, $order);
                return true;
            }
        }

        return false;
    }

    /**
     * proces customers
     * @param $order
     * @return mixed|null|void
     */
    protected function processCustomer($order)
    {
        $converter = $this->getDataConverterByAlias('customer');
        $convertedCustomer = $converter->convertEntity($order);
        $this->setTransportConnector('customer');
        return $this->transport->synchronizeEntity($convertedCustomer);
    }

    /**
     * sync order to Marello
     * @param $order
     * @param $customer
     * @return mixed|null|void
     */
    protected function syncOrder($order, $customer)
    {
        $converter = $this->getDataConverterByAlias('order');
        $converter->setConversionType(OrderDataConverter::ORDER_CONVERSION_TYPE_NEW);
        $convertedOrder = $converter->convertEntity($order);
        // merge customer and order data
        $convertedOrder['customer'] = $customer->id;
        $this->setTransportConnector('order');

        return $this->transport->synchronizeEntity($convertedOrder);
    }

    /**
     * Set and save Marello data on order
     * @param $result
     * @param $order
     * @throws \Exception
     */
    protected function saveOrderData($result, $order)
    {
        $order->setData('marello_data', serialize($result));
        try {
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new \Exception('Could not save the marello data on the order');
        }
    }

    /**
     * @return array|mixed
     */
    public function fetchCustomers()
    {
        $page = 1;
        $result = true;
        $customers = [];
        while ($result) {
            $fetchResult = $this->transport->fetchEntity(['page' => $page], '/customers');
            $results = json_decode($fetchResult);
            if (empty($results)) {
                $results = [];
                $result = false;
            }
            $customers = array_merge($customers, $results);
            $page++;
        }

        return $customers;
    }

    /**
     * find existing customer in All Marello customers
     * @param $email
     * @return mixed|null
     */
    protected function findExistingCustomer($email)
    {
        $existingCustomer = null;
        $decodedResult = $this->fetchCustomers();
        foreach ($decodedResult as $customer) {
            if ($customer->email === $email) {
                $existingCustomer = $customer;
                break;
            }
        }

        return $existingCustomer;
    }
}
