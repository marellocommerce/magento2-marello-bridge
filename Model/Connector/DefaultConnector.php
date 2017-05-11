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
namespace Marello\Bridge\Model\Connector;

use Marello\Bridge\Api\Data\ConnectorInterface;

/**
 * Universal connector
 * Class DefaultConnector
 * @package Marello\Bridge\Model\Connector
 */
class DefaultConnector implements ConnectorInterface
{
    protected $method;

    protected $type;

    /**
     * Get connector method
     * @return mixed
     * @throws \Exception
     */
    public function getMethod()
    {
        if (is_null($this->method)) {
            throw new \Exception('No method set, cannot continue synchronizing');
        }
        return $this->method;
    }

    /**
     * Get request type
     * @return mixed
     */
    public function getType()
    {
        if (is_null($this->type)) {
            $this->setType();
        }
        return $this->type;
    }

    /**
     * Set request type
     * @param string $type
     */
    public function setType($type = 'get')
    {
        $this->type = $type;
    }

    /**
     * Set method for connector
     * @param $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }
}
