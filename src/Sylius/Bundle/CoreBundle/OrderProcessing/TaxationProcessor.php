<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\OrderProcessing;

use Sylius\Bundle\OrderBundle\SyliusAdjustmentEvents;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Component\Addressing\Matcher\ZoneMatcherInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\OrderProcessing\TaxationProcessorInterface;
use Sylius\Component\Order\DTO\AdjustmentDTO;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Taxation processor.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class TaxationProcessor implements TaxationProcessorInterface
{
    /**
     * EventDispatcher.
     *
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Tax calculator.
     *
     * @var CalculatorInterface
     */
    protected $calculator;

    /**
     * Tax rate resolver.
     *
     * @var TaxRateResolverInterface
     */
    protected $taxRateResolver;

    /**
     * Zone matcher.
     *
     * @var ZoneMatcherInterface
     */
    protected $zoneMatcher;

    /**
     * Taxation settings.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param CalculatorInterface      $calculator
     * @param TaxRateResolverInterface $taxRateResolver
     * @param ZoneMatcherInterface     $zoneMatcher
     * @param Settings                 $taxationSettings
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CalculatorInterface $calculator,
        TaxRateResolverInterface $taxRateResolver,
        ZoneMatcherInterface $zoneMatcher,
        Settings $taxationSettings
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->calculator = $calculator;
        $this->taxRateResolver = $taxRateResolver;
        $this->zoneMatcher = $zoneMatcher;
        $this->settings = $taxationSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTaxes(OrderInterface $order)
    {
        // Remove all tax adjustments, we recalculate everything from scratch.
        $order->removeAdjustments(AdjustmentInterface::TAX_ADJUSTMENT);

        if ($order->getItems()->isEmpty()) {
            return;
        }

        $zone = null;

        if (null !== $order->getShippingAddress()) {
            // Match the tax zone.
            $zone = $this->zoneMatcher->match($order->getShippingAddress());
        }

        if ($this->settings->has('default_tax_zone')) {
            // If address does not match any zone, use the default one.
            $zone = $zone ?: $this->settings->get('default_tax_zone');
        }

        if (null === $zone) {
            return;
        }

        $taxValues = $this->processTaxes($order, $zone);
        $this->addAdjustments($taxValues, $order);

        $order->calculateTotal();
    }

    protected function processTaxes(OrderInterface $order, $zone)
    {
        $taxes = array();

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $rate = $this->taxRateResolver->resolve($product, array('zone' => $zone));

            // Skip this item is there is not matching tax rate.
            if (null === $rate) {
                continue;
            }

            $inventoryPrice = $item->getUnitPrice();
            $item->calculateTotal();
            $amount = $this->calculator->calculate($inventoryPrice, $rate);
            $taxAmount = $rate->getAmountAsPercentage();
            $description = sprintf('%s (%s%%)', $rate->getName(), (float) $taxAmount);

            foreach ($item->getInventoryUnits() as $inventoryUnit) {
                $taxes[] = array(
                    'included' => $rate->isIncludedInPrice(),
                    'amount'   => $amount,
                    'inventoryUnit' => $inventoryUnit,
                    'description' => $description,
                    'originType' => get_class($rate),
                    'originId' => 'what to put here in case of tax?',
                );
            }
        }

        return $taxes;
    }

    protected function addAdjustments(array $taxes, $order)
    {
        foreach ($taxes as $tax) {
            $adjustmentDto = new AdjustmentDTO();
            $adjustmentDto->setType(AdjustmentInterface::TAX_ADJUSTMENT);
            $adjustmentDto->setDescription($tax['description']);
            $adjustmentDto->setNeutral($tax['included']);
            $adjustmentDto->setAmount($tax['amount']);
            $adjustmentDto->setOrder($order);
            $adjustmentDto->setInventoryUnit($tax['inventoryUnit']);
            $adjustmentDto->setOriginType($tax['originType']);
            $adjustmentDto->setOriginId($tax['originId']);

            $event = new GenericEvent($adjustmentDto);

            $this->eventDispatcher->dispatch(
                SyliusAdjustmentEvents::INVENTORY_UNIT_LEVEL_ADJUSTMENT,
                $event
            );
        }
    }
}
