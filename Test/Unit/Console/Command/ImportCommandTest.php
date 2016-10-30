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
namespace Marello\Bridge\Test\Unit\Console\Command;

use Symfony\Component\Console\Tester\CommandTester;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State as AppState;

use Marello\Bridge\Console\Command\ImportCommand;
use Marello\Bridge\Model\Processor\ProductProcessor;

class ImportCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImportCommand
     */
    protected $command;

    /**
     * @var ProductProcessor | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productProcessor;

    /**
     * @var AppState | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $appState;

    protected function setUp()
    {
        $this->appState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productProcessor = $this->getMockBuilder(ProductProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ImportCommand(
            $this->appState,
            $this->productProcessor
        );
    }

    /**
     * Test normal command execution
     */
    public function testCommandExecution()
    {
        $this->appState->expects($this->once())
            ->method('setAreaCode')
            ->with(FrontNameResolver::AREA_CODE);

        $this->productProcessor->expects($this->once())
            ->method('process')
            ->willReturn([]);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertContains(
            'Product import has been ran successfully in',
            $commandTester->getDisplay()
        );
    }

    /**
     * Test command execution with exception message
     */
    public function testCommandExecutionWithException()
    {
        $exceptionMessage = 'Something went terribly wrong';
        
        $this->appState->expects($this->once())
            ->method('setAreaCode')
            ->with(FrontNameResolver::AREA_CODE);
        
        $this->productProcessor->expects($this->once())
            ->method('process')
            ->willThrowException(new \Exception($exceptionMessage));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertContains(
            $exceptionMessage,
            $commandTester->getDisplay()
        );
    }
}
