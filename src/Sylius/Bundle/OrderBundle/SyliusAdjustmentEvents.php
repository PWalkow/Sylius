<?php
/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\OrderBundle;

final class SyliusAdjustmentEvents
{
    const ORDER_LEVEL_ADJUSTMENT = 'sylius.adjustment.order';

    const INVENTORY_UNIT_LEVEL_ADJUSTMENT = 'sylius.adjustment.inventory_unit';
}