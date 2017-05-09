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
 * @copyright Copyright 2016 Marello (http://www.marello.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Marello\Bridge\Test\Integration;

use Magento\TestFramework\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\App\DeploymentConfig;

class MarelloBridgeModuleConfigTest extends \PHPUnit_Framework_TestCase
{
    const MODULE_NAME = 'Marello_Bridge';

    /**
     * @var $objectManager ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        /** @var ObjectManager objectManager */
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @test
     */
    public function moduleIsRegisteredInMagento()
    {
        $registrar = new ComponentRegistrar();
        $this->assertArrayHasKey(self::MODULE_NAME, $registrar->getPaths(ComponentRegistrar::MODULE));
    }

    /**
     * @test
     */
    public function moduleIsConfiguredInTheTestEnvironment()
    {
        /** @var $moduleList ModuleList */
        $moduleList = $this->objectManager->create(ModuleList::class);
        $this->assertTrue($moduleList->has(self::MODULE_NAME));
    }

    /**
     * @test
     */
    public function moduleIsConfiguredInTheRealEnvironment()
    {
        /** @var $objectManager ObjectManager */
        $this->objectManager = ObjectManager::getInstance();

        // The tests by default point to the wrong config directory for this test.
        $directoryList = $this->objectManager->create(
            DirectoryList::class,
            ['root' => BP]
        );
        $deploymentConfigReader = $this->objectManager->create(
            Reader::class,
            ['dirList' => $directoryList]
        );
        $deploymentConfig = $this->objectManager->create(
            DeploymentConfig::class,
            ['reader' => $deploymentConfigReader]
        );

        /** @var $moduleList ModuleList */
        $moduleList = $this->objectManager->create(
            ModuleList::class,
            ['config' => $deploymentConfig]
        );
        $this->assertTrue($moduleList->has(self::MODULE_NAME));
    }
}
