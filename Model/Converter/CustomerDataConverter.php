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

use Magento\Customer\Model\ResourceModel\CustomerRepository;

use Marello\Bridge\Api\Data\DataConverterInterface;

class CustomerDataConverter implements DataConverterInterface
{
    /** @var CustomerRepository $customerRepository */
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Prepare a Magento order address to fit the data structure
     * of Marello Order
     * @param $order
     * @return array
     */
    public function convertEntity($order)
    {
        $data = $this->getBasicCustomerData($order);

        // prepare billing address
        $billingAddress = $order->getBillingAddress();
        $billingAddressData = $this->prepareAddressItem($billingAddress);
        $data = array_merge($data, $billingAddressData);

        return $data;
    }

    /**
     * Prepare a Magento address to fit the data structure
     * of Marello Address
     * @param $address
     * @return mixed
     */
    public function prepareAddressItem($address)
    {
        $addressData['primaryAddress'] = [
            'firstName'     => $address->getFirstname(),
            'lastName'      => $address->getLastname(),
            'street'        => $address->getStreetLine(1),
            'street2'       => $address->getStreetLine(2),
            'city'          => $address->getCity(),
            'country'       => $address->getCountryId(),
            'region'        => 'NL-NB',
            'postalCode'    => $address->getPostcode(),
            'phone'         => $address->getTelephone(),
            'company'       => $address->getCompany()
        ];

        return $addressData;
    }

    /**
     * Get basic customer data
     * @param $order
     * @return array
     */
    protected function getBasicCustomerData($order)
    {
        $data = [
            'firstName'      => $order->getBillingAddress()->getFirstname(),
            'lastName'       => $order->getBillingAddress()->getLastname(),
            'email'          => $order->getCustomerEmail(),
        ];

        if (!$order->getCustomerIsGuest()) {
            $customer = $this->customerRepository->get($order->getCustomerEmail());
            $data['firstName'] = $customer->getFirstname();
            $data['lastName'] = $customer->getLastname();
            $data['email'] = $customer->getEmail();
        }

        return $data;
    }
}
