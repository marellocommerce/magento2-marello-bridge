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
namespace Marello\Bridge\Model\Processor;

class RmaProcessor extends AbstractProcessor
{
    /**
     * Process RMA's for export
     * @param array $rmaData
     * @return $this
     */
    public function process(array $rmaData)
    {
        $rma = $rmaData['rma'];
        try {
            $this->syncRma($rma);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Sync rma to Marello
     * @param $rma
     * @return mixed|null|void
     */
    protected function syncRma($rma)
    {
        $converter = $this->getDataConverterByAlias('rma');
        $convertedRma = $converter->convertEntity($rma);
        $this->setTransportConnector('rma');
        $result = $this->transport->synchronizeEntity($convertedRma);

        return $result;
    }

}
