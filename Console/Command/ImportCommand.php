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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\State as AppState;

use Marello\Bridge\Model\Processor\ProductProcessor;

class ImportCommand extends Command
{
    const COMMAND_NAME  = 'marello:cron:import-products';
    /**
     * Cli exit codes
     */
    const RETURN_SUCCESS = 0;
    const RETURN_FAILURE = 1;

    /** @var AppState $appState */
    protected $appState;

    /** @var ProductProcessor $productProcessor */
    protected $productProcessor;

    /**
     * ImportCommand constructor.
     * @param AppState $appState
     * @param ProductProcessor $productProcessor
     */
    public function __construct(
        AppState $appState,
        ProductProcessor $productProcessor
    ) {
        $this->appState = $appState;
        $this->productProcessor = $productProcessor;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Import Data from Marello');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkSetAreaCode();
        $startTime = microtime(true);
        $output->writeln("<info>Starting Product Import</info>");
        try {
            $this->productProcessor->process();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return self::RETURN_FAILURE;
        }

        $resultTime = microtime(true) - $startTime;
        $output->writeln(
            "<info>Product import has been ran successfully in ". gmdate('H:i:s', $resultTime)."</info>"
        );

        return self::RETURN_SUCCESS;
    }

    /**
     * Check if area code is already set
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkSetAreaCode()
    {
        $this->appState->setAreaCode(FrontNameResolver::AREA_CODE);
    }
}
