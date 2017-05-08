<?php

namespace Marello\Bridge\Api;

interface StrategyInterface
{
    const IS_NEW_KEY = 'is_new';

    public function process($item);
}
