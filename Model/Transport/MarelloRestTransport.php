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

use Marello\Bridge\Api\MarelloTransportInterface;
use Marello\Bridge\Api\TransportResultHandlerInterface;
use Marello\Bridge\Api\TransportClientInterface;
use Marello\Bridge\Api\Data\ConnectorInterface;
use Marello\Bridge\Api\Data\TransportSettingsInterface;

class MarelloRestTransport implements MarelloTransportInterface
{
    /** @var TransportClientInterface $client */
    protected $client;

    /** @var TransportSettingsInterface  $settings */
    protected $settings;

    /** @var ConnectorInterface $connector */
    protected $connector;

    /** @var TransportResultHandlerInterface $resultHandler */
    protected $resultHandler;

    /**
     * RestTransport constructor.
     * @param TransportSettingsInterface $settings
     * @param TransportClientInterface $transportClient
     * @param TransportResultHandlerInterface $resultHandler
     */
    public function __construct(
        TransportSettingsInterface $settings,
        TransportClientInterface $transportClient,
        TransportResultHandlerInterface $resultHandler
    ) {
        $this->settings         = $settings;
        $this->client           = $transportClient;
        $this->resultHandler    = $resultHandler;
    }

    /**
     * set connector
     * @param ConnectorInterface $connector
     */
    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return ConnectorInterface
     * @throws \Exception
     */
    public function getConnector()
    {
        if (is_null($this->connector)) {
            throw new \Exception('No connector specified, cannot sync without connector');
        }

        return $this->connector;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeTransport()
    {
        $url = $this->settings->getApiUrl();
        $credentials['username'] = $this->settings->getApiUsername();
        $credentials['api_key'] = $this->settings->getApiKey();
        $this->client->configure($url, $credentials);
    }

    /**
     * {@inheritdoc}
     * @return TransportSettingsInterface
     * @throws \Exception
     */
    public function getTransportSettings()
    {
        if (!$this->settings) {
            throw new \Exception("REST Transport is not configured properly.");
        }

        return $this->settings;
    }

    /**
     * {@inheritdoc}
     * @param $action
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function call($action, $params = [])
    {
        $this->initializeTransport();

        if (!$this->client) {
            throw new \Exception("REST Transport is not configured properly.");
        }

        if (!$this->getIsMarelloApiAvailable()) {
            // throw could not connect exception
            // @codingStandardsIgnoreStart
            $this->resultHandler->handleResponse($this->client->getLastResponse(), $this->client->getResponseCode(), $this->client->getRequestHeaders());
            throw new \Exception('Could not ping the Marello instance, please check your credentials and instance, or contact your system administrator');

            // @codingStandardsIgnoreEnd
        }

        try {
            $connector = $this->getConnector();
            $result = $this->client->restCall($action, $connector->getType(), $params);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $this->resultHandler->handleResponse($this->client->getLastResponse(), $this->client->getResponseCode(), $this->client->getRequestHeaders());
        $this->resultHandler->logResult(TransportResultHandlerInterface::DEBUG, print_r($result, true));
        return $result;
    }

    /**
     * Method doesn't rely on connector so it can have it's own custom method for getting the api status
     * @return mixed
     */
    public function getIsMarelloApiAvailable()
    {
        $this->initializeTransport();
        return $this->client->isMarelloApiAvailable();
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->client->getLastResponse();
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function getRequestHeaders()
    {
        return $this->client->getRequestHeaders();
    }
}
