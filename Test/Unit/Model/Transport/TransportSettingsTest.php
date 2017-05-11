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

use Marello\Bridge\Model\Transport\TransportSettings;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class TransportSettingsTest extends \PHPUnit_Framework_TestCase
{
    protected $scopeConfigMock;

    protected $transportSettings;

    protected $apiKey = 'test1234';

    protected $apiUrl = 'http://example.com';

    protected $apiUsername = 'admin';

    /**
     * setup
     */
    public function setUp()
    {
        // Create a stub for the ScopeConfigInterface class.
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @noinspection PhpParamsInspection */
        $objectManager = new ObjectManager($this);
        $this->transportSettings = $objectManager->getObject(
            TransportSettings::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
            ]
        );
    }

    /**
     * Test get api key from config
     * @covers \Marello\Bridge\Model\Transport\TransportSettings::getApiKey
     */
    public function testGetApiKey()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->equalTo(TransportSettings::XML_PATH_API_KEY))
            ->willReturn($this->apiKey);
        
        $this->assertEquals($this->apiKey, $this->transportSettings->getApiKey());
    }

    /**
     * Test get api url from config
     * @covers \Marello\Bridge\Model\Transport\TransportSettings::getApiUrl
     */
    public function testGetApiUrl()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->equalTo(TransportSettings::XML_PATH_API_URL))
            ->willReturn($this->apiUrl);

        $this->assertEquals($this->apiUrl, $this->transportSettings->getApiUrl());
    }

    /**
     * Test get api user name from config
     * @covers \Marello\Bridge\Model\Transport\TransportSettings::getApiUsername
     */
    public function testGetApiUsername()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->equalTo(TransportSettings::XML_PATH_API_USERNAME))
            ->willReturn($this->apiUsername);

        $this->assertEquals($this->apiUsername, $this->transportSettings->getApiUsername());
    }
}
