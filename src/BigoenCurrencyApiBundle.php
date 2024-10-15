<?php

declare(strict_types=1);

namespace Bigoen\CurrencyApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Şafak Saylam <safak@bigoen.com>
 */
class BigoenCurrencyApiBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
