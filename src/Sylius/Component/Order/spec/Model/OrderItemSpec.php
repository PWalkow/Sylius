<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Component\Order\Model;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Order\Model\AdjustmentInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderItemInterface;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class OrderItemSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Component\Order\Model\OrderItem');
    }

    function it_implements_Sylius_order_item_interface()
    {
        $this->shouldImplement('Sylius\Component\Order\Model\OrderItemInterface');
    }

    function it_has_no_id_by_default()
    {
        $this->getId()->shouldReturn(null);
    }

    function it_does_not_belong_to_an_order_by_default()
    {
        $this->getOrder()->shouldReturn(null);
    }

    function it_allows_assigning_itself_to_an_order(OrderInterface $order)
    {
        $this->setOrder($order);
        $this->getOrder()->shouldReturn($order);
    }

    function it_allows_detaching_itself_from_an_order(OrderInterface $order)
    {
        $this->setOrder($order);
        $this->getOrder()->shouldReturn($order);

        $this->setOrder(null);
        $this->getOrder()->shouldReturn(null);
    }

    function it_has_quantity_equal_to_1_by_default()
    {
        $this->getQuantity()->shouldReturn(1);
    }

    function its_quantity_is_mutable()
    {
        $this->setQuantity(8);
        $this->getQuantity()->shouldReturn(8);
    }

    function it_has_unit_price_equal_to_0_by_default()
    {
        $this->getUnitPrice()->shouldReturn(0);
    }

    function its_unit_price_should_accept_only_integer()
    {
        $this->setUnitPrice(4498);
        $this->getUnitPrice()->shouldBeInteger();
        $this->shouldThrow('\InvalidArgumentException')->duringSetUnitPrice(44.98 * 100);
        $this->shouldThrow('\InvalidArgumentException')->duringSetUnitPrice('4498');
        $this->shouldThrow('\InvalidArgumentException')->duringSetUnitPrice(round(44.98 * 100));
        $this->shouldThrow('\InvalidArgumentException')->duringSetUnitPrice(array(4498));
        $this->shouldThrow('\InvalidArgumentException')->duringSetUnitPrice(new \stdClass());
    }

    function it_has_total_equal_to_0_by_default()
    {
        $this->getTotal()->shouldReturn(0);
    }

    function its_total_should_accept_only_integer()
    {
        $this->setTotal(4498);
        $this->getTotal()->shouldBeInteger();
        $this->shouldThrow('\InvalidArgumentException')->duringSetTotal(44.98 * 100);
        $this->shouldThrow('\InvalidArgumentException')->duringSetTotal('4498');
        $this->shouldThrow('\InvalidArgumentException')->duringSetTotal(round(44.98 * 100));
        $this->shouldThrow('\InvalidArgumentException')->duringSetTotal(array(4498));
        $this->shouldThrow('\InvalidArgumentException')->duringSetTotal(new \stdClass());
    }

    function it_throws_exception_when_quantity_is_less_than_1()
    {
        $this
            ->shouldThrow(new \OutOfRangeException('Quantity must be greater than 0.'))
            ->duringSetQuantity(-5)
        ;
    }

    function its_total_is_mutable()
    {
        $this->setTotal(5999);
        $this->getTotal()->shouldReturn(5999);
    }

    function it_calculates_correct_total_based_on_quantity_and_unit_price()
    {
        $this->setQuantity(13);
        $this->setUnitPrice(1499);

        $this->calculateTotal();

        $this->getTotal()->shouldReturn(19487);
    }

    function it_calculates_correct_total_based_on_adjustments(AdjustmentInterface $adjustment)
    {
        $this->setQuantity(13);
        $this->setUnitPrice(1499);

        $this->calculateTotal();

        $this->getTotal()->shouldReturn(13 * 1499);
    }

    function it_ignores_merging_same_items()
    {
        $this->merge($this);
        $this->getQuantity()->shouldReturn(1);
    }

    function it_merges_an_equal_item_by_summing_quantities(OrderItemInterface $item)
    {
        $this->setQuantity(3);

        $item->getQuantity()->willReturn(7);
        $item->equals($this)->willReturn(true);

        $this->merge($item);
        $this->getQuantity()->shouldReturn(10);
    }

    function it_merges_a_known_equal_item_without_calling_equals(OrderItemInterface $item)
    {
        $this->setQuantity(3);

        $item->getQuantity()->willReturn(7);
        $item->equals($this)->shouldNotBeCalled();

        $this->merge($item, false);
        $this->getQuantity()->shouldReturn(10);
    }

    function it_throws_exception_when_merging_unequal_item(OrderItemInterface $item)
    {
        $item->equals($this)->willReturn(false);

        $this
            ->shouldThrow(new \RuntimeException('Given item cannot be merged.'))
            ->duringMerge($item);
    }
}
