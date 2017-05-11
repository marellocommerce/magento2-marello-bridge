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
namespace Marello\Bridge\Test\Unit\Model\Transport;

use Psr\Log\LoggerInterface;

use Marello\Bridge\Model\Transport\TransportResultHandler;

class TransportResultHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $loggerMock;

    protected $resultHandler;
    /**
     * setup
     */
    public function setUp()
    {
        // Create a stub for the LoggerInterface class.
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultHandler = new TransportResultHandler($this->loggerMock);
    }

    /**
     * Call protected methods for testing
     * @param $obj
     * @param $name
     * @param array $args
     * @return mixed
     */
    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * @test
     */
    public function successfullyMergeResultAndParametersToArrayWithObjects()
    {
        $result = [$this->loggerMock];
        $params = $this->loggerMock;
        $testMergedResult = array_merge($result, [$params]);

        $mergedResult = $this->callMethod(
            $this->resultHandler,
            'mergeResultAndParametersToArray',
            [$result, $params]
        );

        $this->assertTrue(is_array($mergedResult));
        $this->assertEquals($testMergedResult, $mergedResult);
    }

    /**
     * @test
     */
    public function successfullyMergeResultAndParametersToArrayWithPlainData()
    {
        $result = 'testResult';
        $params = ['testparam1', 'testparam2'];
        $testMergedResult = array_merge([$result], $params);

        $mergedResult = $this->callMethod(
            $this->resultHandler,
            'mergeResultAndParametersToArray',
            [$result, $params]
        );

        $this->assertTrue(is_array($mergedResult));
        $this->assertEquals($testMergedResult, $mergedResult);
    }
}
