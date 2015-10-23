<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Bundle\CoreBundle\OrderProcessing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\OrderBundle\SyliusAdjustmentEvents;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Component\Addressing\Matcher\ZoneMatcherInterface;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\TaxRateInterface;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;
use Sylius\Component\Taxation\Model\TaxableInterface;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class TaxationProcessorSpec extends ObjectBehavior
{
    function let(
        EventDispatcherInterface $eventDispatcher,
        CalculatorInterface $calculator,
        TaxRateResolverInterface $taxRateResolver,
        ZoneMatcherInterface $zoneMatcher,
        Settings $taxationSettings
    ) {
        $this->beConstructedWith($eventDispatcher, $calculator, $taxRateResolver, $zoneMatcher, $taxationSettings);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Bundle\CoreBundle\OrderProcessing\TaxationProcessor');
    }

    function it_implements_Sylius_taxation_processor_interface()
    {
        $this->shouldImplement('Sylius\Component\Core\OrderProcessing\TaxationProcessorInterface');
    }

    function it_removes_existing_tax_adjustments(OrderInterface $order, Collection $collection)
    {
        $collection->isEmpty()->willReturn(true);

        $order->getItems()->willReturn($collection);
        $order->removeAdjustments(Argument::any())->shouldBeCalled();

        $this->applyTaxes($order);
    }

    function it_doesnt_apply_any_taxes_if_zone_is_missing(
        OrderInterface $order,
        $taxationSettings
    ) {
        $collection = new ArrayCollection();

        $order->getItems()->willReturn($collection);
        $order->removeAdjustments(Argument::any())->shouldBeCalled();

        $order->getShippingAddress()->willReturn(null);

        $taxationSettings->has('default_tax_zone')->willReturn(false);

        $order->addAdjustment(Argument::any())->shouldNotBeCalled();

        $this->applyTaxes($order);
    }

    function it_adds_tax_adjustments_for_each_inventory_unit_within_order_items(
        Order $order,
        OrderItem $firstOrderItem,
        OrderItem $secondOrderItem,
        ZoneInterface $zone,
        TaxableInterface $taxableProduct,
        TaxRateInterface $taxRate,
        $zoneMatcher,
        $taxationSettings,
        $taxRateResolver,
        $eventDispatcher
    ) {
        $orderItems = new ArrayCollection();
        $orderItems->add($firstOrderItem->getWrappedObject());
        $orderItems->add($secondOrderItem->getWrappedObject());

        $taxationSettings->has('default_tax_zone')->willReturn(true);
        $taxationSettings->get('default_tax_zone')->willReturn($zone);
        $zoneMatcher->match()->shouldNotBeCalled();
        $order->getShippingAddress()->willReturn(null);

        $order->getItems()->willReturn($orderItems);

        $order->removeAdjustments(AdjustmentInterface::TAX_ADJUSTMENT)->shouldBeCalled();

        $firstOrderItem->getProduct()->shouldBeCalledTimes(1)->willReturn($taxableProduct);
        $secondOrderItem->getProduct()->shouldBeCalledTimes(1)->willReturn($taxableProduct);

        $taxRateResolver->resolve(
            $taxableProduct,
            array('zone' => $zone)
        )->shouldBeCalledTimes(2)->willReturn($taxRate);

        $firstOrderItem->getQuantity()->willReturn(2);
        $firstOrderItem->getUnitPrice()->willReturn(123);
        $firstOrderItem->calculateTotal()->shouldBeCalled();
        $firstOrderItem->getId()->willReturn(234);

        $secondOrderItem->getQuantity()->willReturn(1);
        $secondOrderItem->getUnitPrice()->willReturn(321);
        $secondOrderItem->calculateTotal()->shouldBeCalled();
        $secondOrderItem->getId()->willReturn(235);

        $order->getId()->willReturn(52);
        $order->calculateTotal()->shouldBeCalled();

        $eventDispatcher->dispatch(
            SyliusAdjustmentEvents::INVENTORY_UNIT_LEVEL_ADJUSTMENT,
            Argument::any()
        )->shouldBeCalledTimes(3);

        $this->applyTaxes($order);
    }
}
