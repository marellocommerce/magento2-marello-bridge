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
 * @package   Marello
 * @copyright Copyright Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Test\Integration;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

use Magento\Framework\Console\Cli;
use Magento\TestFramework\Helper\Bootstrap;

use Marello\Bridge\Test\Integration\Stub\TransportClientMock;
use Marello\Bridge\Console\Command\ApplicationStatusCommand;
use Marello\Bridge\Api\TransportClientInterface;

class MarelloBridgeApiCheckApplicationStatusTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\TestFramework\ObjectManager $objectManager */
    protected $objectManager;

    protected $transportClientMock;

    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        // Mock TransportClient for testing in 'full' with 3rd party api
        $this->objectManager->configure(
            ['preferences' => [TransportClientInterface::class => TransportClientMock::class]]
        );

        $this->transportClientMock = $this->objectManager->get(TransportClientMock::class);
    }

    /**
     * @test
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadFixture
     * 1) Get Magento's Cli
     * 2) Verify that the application has picked up ApplicationStatusCommand
     * 3) Setup Input & Output for command to run
     * 4) Run command
     * 5) Verify that command ran succesfully
     */
    public function commandSendRequestToMarelloAndGetApplicationStatusIsAvailable()
    {
        /** @var Cli $cliApplication */
        $cliApplication = $this->objectManager->get(Cli::class);
        $this->assertTrue($cliApplication->has(ApplicationStatusCommand::COMMAND_NAME));

        // when running in cli application add the ['command' => ApplicationStatusCommand::COMMAND_NAME] instead
        // of empty array
        $input = new ArrayInput([]);
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $command = $this->objectManager->get(ApplicationStatusCommand::class);
        $result = $command->run($input, $output);

        $this->assertNotEmpty($this->transportClientMock->getMessage('isMarelloApiAvailable'));
        $this->assertCount(2, $this->transportClientMock->getMessage('isMarelloApiAvailable'));
        $this->assertContains('Marello API Is Available', $this->transportClientMock->getMessage('isMarelloApiAvailable'));
        $this->assertEquals(ApplicationStatusCommand::RETURN_SUCCESS, $result);

        $this->resetTransportClient();
    }

    protected function resetTransportClient()
    {
        // reset the all received messages
        $this->transportClientMock->removeAllMessages();

        // just checking that it is actually empty
        $this->assertEmpty($this->transportClientMock->getMessages());
    }

    /**
     * 'Workaround' for loading fixtures from files
     * which are not in the Magento testsuite
     */
    public static function loadFixture()
    {
        include __DIR__ . '/_files/module-config.php';
    }
}
