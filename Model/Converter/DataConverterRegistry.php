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
namespace Marello\Bridge\Model\Converter;

use Marello\Bridge\Api\Data\DataConverterRegistryInterface;

class DataConverterRegistry implements DataConverterRegistryInterface
{
    /** @var array $converters */
    protected $converters = [];

    /**
     * DataConverterRegistry constructor.
     * @param array $converters
     */
    public function __construct(array $converters = [])
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataConverters()
    {
        return $this->converters;
    }
}
