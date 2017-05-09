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
namespace Marello\Bridge\Model\Reader;

use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Api\ItemReaderInterface;
use Marello\Bridge\Model\Transport\MarelloTransportInterface;

class ProductReader implements ItemReaderInterface
{
    /**
     * @var bool
     */
    protected $result = true;

    /**
     * @var MarelloTransportInterface
     */
    protected $transport;

    /**
     * @var ConnectorRegistryInterface
     */
    protected $connectors;

    public function __construct(
        MarelloTransportInterface $transport,
        ConnectorRegistryInterface $registry
    ) {
        $this->transport = $transport;
        $this->connectors = $registry;
    }

    /**
     * @return array|null
     */
    public function read()
    {
        $page = 1;
        $products = [];
        $connector = $this->getConnectorByAlias('default', 'import');
        $connector->setMethod('/products');
        $this->transport->setConnector($connector);
        while ($this->result) {
            $result = $this->transport->call('/products', ['page' => $page, 'limit' => 10]);
            $fetchResult = $result->getData('body');
            $results = json_decode($fetchResult);
            if (empty($results)) {
                $results = [];
                $this->result = false;
            }

            $products = array_merge($products, $results);
            $page++;
        }

        if (count($products) > 0) {
            return $products;
        }

        return null;
    }

    /**
     * If found in registry return the connector for given alias
     * @param $alias
     * @param string $type
     * @return mixed
     */
    protected function getConnectorByAlias($alias, $type = 'export')
    {
        $connectors = $this->connectors->getConnectors();
        if (!isset($connectors[$type][$alias]) && empty($connectors[$type][$alias])) {
            throw new \InvalidArgumentException(
                sprintf('No connector found for alias "%s" in context type "%s"', $alias, $type)
            );
        }

        return $connectors[$type][$alias];
    }
}
