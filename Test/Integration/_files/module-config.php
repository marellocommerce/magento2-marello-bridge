<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Config\Model\ResourceModel\Config $config */
$config = $objectManager->get('Magento\Config\Model\ResourceModel\Config');

$configData = [
    'marellobridgesettings/general/enabled'         => 1,
    'marellobridgesettings/general/api_url'         => 'http://www.example.com',
    'marellobridgesettings/general/api_key'         => 'admin1234',
    'marellobridgesettings/general/api_username'    => 'admin'
];


foreach ($configData as $path => $value) {
    $config->saveConfig(
        $path,
        $value,
        \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        0
    );
}

$objectManager->get('Magento\Framework\App\Config\ReinitableConfigInterface')->reinit();
$objectManager->create('Magento\Store\Model\StoreManagerInterface')->reinitStores();
