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
namespace Marello\Bridge\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State as AppState;

use Marello\Bridge\Model\Transport\MarelloTransportInterface;

class ApplicationStatusCommand extends Command
{
    const COMMAND_NAME  = 'marello:app:status';
    /**
     * Cli exit codes
     */
    const RETURN_SUCCESS = 0;
    const RETURN_FAILURE = 1;

    /** @var AppState $appState */
    protected $appState;

    /** @var MarelloTransportInterface $transport */
    protected $transport;

    /**
     * ImportCommand constructor.
     * @param MarelloTransportInterface $transport
     * @param AppState $appState
     */
    public function __construct(
        MarelloTransportInterface $transport,
        AppState $appState
    ) {
        $this->transport = $transport;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Check if Marello is available');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setAreaCode();

        $output->writeln("<info>Checking Marello Application availability status</info>");
        try {
            $this->transport->initializeTransport();
            $result = $this->transport->getIsMarelloApiAvailable();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            throw new \Exception($e->getMessage());
        }

        if ($result) {
            $output->writeln("<info>Marello Application availability status: {$result}</info>");
            return self::RETURN_SUCCESS;
        }

        return self::RETURN_FAILURE;
    }

    /**
     * Set Area Code
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setAreaCode()
    {
        $this->appState->setAreaCode(FrontNameResolver::AREA_CODE);
    }
}
