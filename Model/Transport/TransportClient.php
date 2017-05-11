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

use Marello\Api\Client;

class TransportClient implements TransportClientInterface
{
    const REST_CALL_TYPE_GET    = 'get';
    const REST_CALL_TYPE_PUT    = 'put';
    const REST_CALL_TYPE_POST   = 'post';
    const REST_CALL_TYPE_DELETE = 'delete';

    /** @var Client $client */
    protected $client;

    /**
     * {@inheritdoc}
     * @param $url
     * @param array $params
     */
    public function configure($url, $params = [])
    {
        $this->client = $this->createNewClient($url);
        $this->client->setAuth($params);
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isMarelloApiAvailable()
    {
        return $this->client->pingUsers();
    }

    /**
     * {@inheritdoc}
     * @param $path
     * @param string $type
     * @param array $query
     * @return mixed
     */
    public function restCall($path, $type = 'get', array $query = [])
    {
        switch ($type) :
            case self::REST_CALL_TYPE_GET:
                $result = $this->restGetCall($path, $query);
                break;
            case self::REST_CALL_TYPE_POST:
                $result = $this->restPostCall($path, $query);
                break;
            case self::REST_CALL_TYPE_PUT:
                $result = $this->restPutCall($path, $query);
                break;
            case self::REST_CALL_TYPE_DELETE:
                $result = $this->restDeleteCall($path, $query);
                break;
            default:
                $result = $this->restGetCall($path, $query);
        endswitch;

        return $result;
    }

    /**
     * {@inheritdoc}
     * @param $path
     * @param $query
     * @return mixed
     */
    protected function restGetCall($path, $query)
    {
        return $this->client->restGet($path, $query);
    }

    /**
     * {@inheritdoc}
     * @param $path
     * @param $query
     * @return mixed
     */
    protected function restPostCall($path, $query)
    {
        return $this->client->restPost($path, $query);
    }

    /**
     * {@inheritdoc}
     * @param $path
     * @param $query
     * @return mixed
     */
    protected function restPutCall($path, $query)
    {
        return $this->client->restPut($path, $query);
    }

    /**
     * {@inheritdoc}
     * @param $path
     * @param $query
     * @return mixed
     */
    protected function restDeleteCall($path, $query)
    {
        return $this->client->restDelete($path, $query);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function getResponseCode()
    {
        return $this->client->getResponseCode();
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
        return $this->client->getLastRequestHeaders();
    }

    /**
     * {@inheritdoc}
     * @param $url
     * @return Client
     */
    public function createNewClient($url)
    {
        return new Client($url);
    }
}
