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
namespace Marello\Bridge\Test\Integration\Stub;

use Marello\Bridge\Model\Transport\TransportClient;

class TransportClientMock extends TransportClient
{
    protected $messages = [];

    protected $callTotal = 0;

    protected $dummyResponse;

    public function getMessage($identifier)
    {
        return $this->messages[$identifier];
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function removeAllMessages()
    {
        if (count($this->messages) > 0) {
            $this->messages = [];
        }
    }

    protected function addMessage($identifier, $message)
    {
        // check if the message already has been set and if so add both previous and new message.
        if (isset($this->messages[$identifier])) {
            $this->messages[$identifier] = [$this->messages[$identifier], $message];
        } else {
            $this->messages[$identifier] = $message;
        }
    }

    public function setDummyResponseData($jsonEncodedString)
    {
        $this->dummyResponse = $jsonEncodedString;
    }

    public function clearDummyResponseData()
    {
        $this->dummyResponse = null;
    }

    public function getDummyResponse()
    {
        return $this->dummyResponse;
    }

    public function resetTotalCalls()
    {
        $this->callTotal = 0;
    }

    public function getTotalCalls()
    {
        return $this->callTotal;
    }

    /**
     * @return bool
     */
    public function isMarelloApiAvailable()
    {
        $this->addMessage(__FUNCTION__, 'Marello API Is Available');
        return true;
    }

    protected function restGetCall($path, $query)
    {
        $this->addMessage(__FUNCTION__, 'Marello API Get Call');
        if ($this->callTotal === 0) {
            $this->callTotal++;
            return $this->getDummyResponse();
        }

        return null;
    }

    protected function restPostCall($path, $query)
    {
        return;
    }

    protected function restPutCall($path, $query)
    {
        return;
    }

    protected function restDeleteCall($path, $query)
    {
        return;
    }

    public function getResponseCode()
    {
        return;
    }

    public function getLastResponse()
    {
        return;
    }

    public function getRequestHeaders()
    {
        return;
    }

    public function createNewClient($url)
    {
        return new \stdClass();
    }
}
