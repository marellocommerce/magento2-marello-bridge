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
namespace Marello\Bridge\Api;

interface TransportClientInterface
{
    /**
     * @param $url
     * @param array $params
     * @return mixed
     */
    public function configure($url, $params = []);

    /**
     * @return mixed
     */
    public function isMarelloApiAvailable();

    /**
     * @param $path
     * @param string $type
     * @param array $query
     * @return mixed
     */
    public function restCall($path, $type = 'get', array $query = []);

    /**
     * @return mixed
     */
    public function getResponseCode();

    /**
     * @return mixed
     */
    public function getLastResponse();

    /**
     * @return mixed
     */
    public function getRequestHeaders();
}
