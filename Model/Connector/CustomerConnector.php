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
namespace Marello\Bridge\Model\Connector;

use Marello\Bridge\Api\Data\ConnectorInterface;

class CustomerConnector implements ConnectorInterface
{
    /**
     * Get method for connector
     * @return string
     */
    public function getMethod()
    {
        return '/customers';
    }

    /**
     * Get request type
     * @return string
     */
    public function getType()
    {
        return 'post';
    }
}
