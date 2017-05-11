<?php

namespace Marello\Bridge\Ui\Component\AdminGrid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ExportStatus extends Column
{
    /**
     * {@inheritdoc}
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['marello_export_status'] = $this->getCurrentStatus(
                    $item['marello_export_status'],
                    $item['status']);
            }
        }

        return $dataSource;
    }

    /**
     * {@inheritdoc}
     * @param $result
     * @param $orderStatus
     * @return \Magento\Framework\Phrase
     */
    protected function getCurrentStatus($result, $orderStatus)
    {
        $currentStatus = __('No data available');

        if ($result === null && $orderStatus === 'pending') {
            $currentStatus = __('Not exported');
        }

        if ($result !== null) {
            $currentStatus = __('Exported');
        }


        return $currentStatus;
    }
}
