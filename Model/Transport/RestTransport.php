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
namespace Marello\Bridge\Model\Transport;

use Psr\Log\LoggerInterface;

use Marello\Bridge\Api\Data\ConnectorInterface;
use Marello\Bridge\Api\Data\TransportSettingsInterface;
use Marello\Api\Client;

class RestTransport
{
    /** maximum attempts */
    const MAX_ATTEMPTS = 5;

    /** @var Client $client */
    protected $client;

    /** @var TransportSettingsInterface  $settings */
    protected $settings;

    /** @var ConnectorInterface $connector */
    protected $connector;

    /** @var LoggerInterface $logger */
    protected $logger;

    /**
     * RestTransport constructor.
     * @param TransportSettingsInterface $settings
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportSettingsInterface $settings,
        LoggerInterface $logger
    ) {
        $this->settings = $settings;
        $this->logger = $logger;
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

    public function fetchEntity($data, $method, $attempts = 0)
    {
        $client = $this->getClient();
        $result = null;
        $result = $client->restGet($method, $data);
        $this->logResult($data, 1, $result);
        return $result;
    }

    public function synchronizeEntity($preparedEntityData, $attempts = 0)
    {
        $connector = $this->getConnector();
        $type = $connector->getType();
        $client = $this->getClient();
        $result = null;

        switch ($type) :
            case 'put':
                $result = $client->restPut($connector->getMethod(), $preparedEntityData);
                break;
            case 'post':
                $result = $client->restPost($connector->getMethod(), $preparedEntityData);
                break;
            default:
                $result = $client->restGet($connector->getMethod(), $preparedEntityData);
                break;
        endswitch;

        $this->logResult($preparedEntityData, $attempts, $result);

        return $result;
    }


    protected function logResult($preparedEntityData, $attempts, $result)
    {
        $responseCode = $this->client->getResponseCode();
        switch ($responseCode) :
            case 200:
                $this->logger->info('Successful fetch of entity');
                $this->logger->info(print_r($result, true));
                break;
            case 201:
                $this->logger->info('Entity Created');
                $this->logger->info(print_r($result, true));
                $this->logger->info(print_r($preparedEntityData, true));
                break;
            case 400:
                $this->handleBadRequest($preparedEntityData);
                break;
            case 401:
                $this->handleNotAuthorized($preparedEntityData, $attempts);
                break;
            case 500:
                $this->handleInternalServerError($preparedEntityData);
                break;
            default:
                // @codingStandardsIgnoreStart
                $this->logger->emergency(
                    'Something\'s up, it didn\'t respond with any of the defined result codes. Response code is: ' . $responseCode
                );
                // @codingStandardsIgnoreEnd
                break;
        endswitch;
    }

    /**
     * Handle bad request (HTTP CODE 400)
     * @param $preparedEntityData
     */
    protected function handleBadRequest($preparedEntityData)
    {
        // @codingStandardsIgnoreStart
        $this->logger->error(
            'Tried to do a '.$this->connector->getType().' request with calling '. $this->connector->getMethod() . ', but failed. Data send: '
        );
        // @codingStandardsIgnoreEnd
        $this->logger->error(print_r($preparedEntityData, true));
        return;
    }

    /**
     * Handle not authorized requests (HTTP CODE 401)
     * @param $preparedEntityData
     * @param $attempts
     */
    protected function handleNotAuthorized($preparedEntityData, $attempts)
    {
        // not authorized try again
        if ($attempts < self::MAX_ATTEMPTS) {
            $attempts++;
            $this->synchronizeEntity($preparedEntityData, null, $attempts);
        } else {
            // @codingStandardsIgnoreStart
            $this->logger->emergency(
                'Could not authorize request in 5 attempts please make sure all the credentials are correct in the backend'
            );
            // @codingStandardsIgnoreEnd
            return;
        }
    }

    /**
     * Handle internal server error (HTTP CODE 500)
     * @param $preparedEntityData
     */
    protected function handleInternalServerError($preparedEntityData)
    {
        // @codingStandardsIgnoreStart
        $this->logger->critical(
            'Failed sending data with method '. $this->getConnector()->getMethod() . ' ' . $this->connector->getType() . '.  Data send: '
        );
        // @codingStandardsIgnoreEnd
        $this->logger->critical(print_r($preparedEntityData, true));
        return;
    }

    /**
     * Get API client to establish connection with Marello
     * @return Client
     * @throws \Exception
     */
    protected function getClient()
    {
        $url = $this->settings->getApiUrl();
        $this->client = new Client($url);
        $credentials['username'] = $this->settings->getApiUsername();
        $credentials['api_key'] = $this->settings->getApiKey();
        $this->client->setAuth($credentials);

        if (!$this->client->pingUsers()) {
            // throw could not connect exception
            // @codingStandardsIgnoreStart
            $this->logger->alert('Could not ping the Marello instance, please check your credentials and instance, or contact your system administrator');
            throw new \Exception('Could not ping the Marello instance, please check your credentials and instance, or contact your system administrator');
            // @codingStandardsIgnoreEnd
        }

        return $this->client;
    }
}
