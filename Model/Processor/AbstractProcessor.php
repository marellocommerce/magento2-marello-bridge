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

use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Api\Data\ConnectorRegistryInterface;
use Marello\Bridge\Model\Transport\RestTransport;

abstract class AbstractProcessor
{
    /** @var ConnectorRegistryInterface $connectorRegistry */
    protected $connectorRegistry;
    
    /** @var DataConverterRegistryInterface $converterRegistry */
    protected $converterRegistry;

    /** @var RestTransport $transport */
    protected $transport;

    /**
     * AbstractProcessor constructor.
     * @param ConnectorRegistryInterface $connectorRegistry
     * @param DataConverterRegistryInterface $converterRegistry
     * @param RestTransport $transport
     */
    public function __construct(
        ConnectorRegistryInterface $connectorRegistry,
        DataConverterRegistryInterface $converterRegistry,
        RestTransport $transport
    ) {
        $this->connectorRegistry = $connectorRegistry;
        $this->converterRegistry = $converterRegistry;
        $this->transport = $transport;
    }

    /**
     * Get DataConverter Instance by alias
     * @param string null $alias
     * @throws \InvalidArgumentException
     * @return DataConverterInterface
     */
    protected function getDataConverterByAlias($alias)
    {
        $converters = $this->converterRegistry->getDataConverters();
        if (!isset($converters[$alias]) && empty($converters[$alias])) {
            throw new \InvalidArgumentException(sprintf('No converter found for alias "%s"', $alias));
        }

        return $converters[$alias];
    }

    /**
     * If found in registry return the connector for given alias
     * @param $alias
     * @param string $type
     * @return mixed
     */
    protected function getConnectorByAlias($alias, $type = 'export')
    {
        $connectors = $this->connectorRegistry->getConnectors();
        if (!isset($connectors[$type][$alias]) && empty($connectors[$type][$alias])) {
            throw new \InvalidArgumentException(sprintf('No connector found for alias "%s" in context type "%s"', $alias, $type));
        }

        return $connectors[$type][$alias];
    }

    /**
     * Set transport connector for syncing with correct connector
     * @param $connectorAlias
     * @param string $type
     */
    protected function setTransportConnector($connectorAlias, $type = 'export')
    {
        // set connector for transport...
        $connector = $this->getConnectorByAlias($connectorAlias, $type);
        $this->transport->setConnector($connector);
    }
}
