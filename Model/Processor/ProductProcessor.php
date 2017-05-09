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

use Marello\Bridge\Api\Data\DataConverterRegistryInterface;
use Marello\Bridge\Api\Data\DataConverterInterface;
use Marello\Bridge\Api\StrategyInterface;

class ProductProcessor implements MarelloProcessorInterface
{
    /** @var StrategyInterface $strategy */
    protected $strategy;

    /** @var DataConverterInterface $converter  */
    protected $converter;

    /** @var DataConverterRegistryInterface $converters */
    protected $converters;

    public function __construct(
        StrategyInterface $strategy,
        DataConverterRegistryInterface $converterRegistry
    ) {
        $this->strategy = $strategy;
        $this->converters = $converterRegistry;
    }

    /**
     * @param $items
     * @return array|null
     */
    public function process($items)
    {
        $this->initialize();
        $processedItems = [];

        foreach ($items as $entity) {
            $itemData = $this->converter->convertEntity($entity);
            $processedItems[] = $this->strategy->process($itemData);
        }

        if (count($processedItems) <= 0) {
            return null;
        }

        return $processedItems;
    }

    /**
     * Get DataConverter Instance by alias
     * @param string null $alias
     * @throws \InvalidArgumentException
     * @return DataConverterInterface
     */
    protected function getDataConverterByAlias($alias)
    {
        if (!is_null($this->converter)) {
            return $this->converter;
        }

        $converters = $this->converters->getDataConverters();
        if (!isset($converters[$alias]) && empty($converters[$alias])) {
            throw new \InvalidArgumentException(sprintf('No converter found for alias "%s"', $alias));
        }

        return $converters[$alias];
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->converter = $this->getDataConverterByAlias('product');
    }
}
