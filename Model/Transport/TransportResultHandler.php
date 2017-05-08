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

class TransportResultHandler implements TransportResultHandlerInterface
{
    /** @var LoggerInterface $logger */
    protected $logger;

    /**
     * TransportResultHandler constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle responses based on the responseCode the api returns
     * @param $result
     * @param $responseCode
     * @param array $params
     */
    public function handleResponse($result, $responseCode, $params = [])
    {
        switch ($responseCode) :
            case 200:
                $this->logResult(
                    TransportResultHandlerInterface::INFO,
                    'Successful fetch of entity',
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
            case 201:
                $this->logResult(
                    TransportResultHandlerInterface::INFO,
                    'Entity Created',
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
            case 400:
                $this->logResult(
                    TransportResultHandlerInterface::ERROR,
                    'Bad Request, please check your request headers to verify they comply with the structure of the API',
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
            case 401:
                $this->logResult(
                    TransportResultHandlerInterface::EMERGENCY,
                    'Not authorized to do a successful request, please check your credentials and or your API user account',
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
            case 500:
                $this->logResult(
                    TransportResultHandlerInterface::CRITICAL,
                    'An error occurred on the API side, please check the API to verify it\'s not down and or what may have caused this error',
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
            default:
                $this->logResult(
                    TransportResultHandlerInterface::EMERGENCY,
                    sprintf(
                        'Something\'s up, it didn\'t correspond with any of the defined result codes. Response code: %s',
                        $responseCode
                    ),
                    $this->mergeResultAndParametersToArray($result, $params)
                );
                break;
        endswitch;
    }

    /**
     * Log the result of the response/result
     * @param $level
     * @param $message
     * @param $context
     */
    public function logResult($level, $message, $context = [])
    {
        $this->logger->log($level, $message, $context = array());
    }

    /**
     * Merge the result and parameters together in one array
     * @param $result
     * @param $params
     * @return array
     * @throws \Exception
     */
    private function mergeResultAndParametersToArray($result, $params)
    {
        if (!is_array($result)) {
            $result = [$result];
        }

        if (!is_array($params)) {
            $params = [$params];
        }

        try {
            $mergedResult = array_merge($result, $params);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $mergedResult;
    }
}
