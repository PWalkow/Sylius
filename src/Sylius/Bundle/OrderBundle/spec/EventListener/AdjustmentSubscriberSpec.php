<?php

namespace spec\Sylius\Bundle\OrderBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\DTO\AdjustmentDTO;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AdjustmentSubscriberSpec extends ObjectBehavior
{
    function let(
        RepositoryInterface $adjustmentRepository,
        RepositoryInterface $orderRepository,
        RepositoryInterface $orderItemRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->beConstructedWith($adjustmentRepository, $orderRepository, $orderItemRepository, $entityManager);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Bundle\OrderBundle\EventListener\AdjustmentSubscriber');
    }

    function it_add_adjustment_on_order_level(
        $adjustmentRepository,
        $orderRepository,
        $entityManager,
        GenericEvent $event,
        AdjustmentDTO $dto,
        AdjustmentInterface $adjustment,
        OrderInterface $order
    ) {
        $event->getSubject()->shouldBeCalled()->willReturn($dto);

        $adjustmentRepository->createNew()->shouldBeCalled()->willReturn($adjustment);

        $dto->getType()->willReturn(AdjustmentInterface::TAX_ADJUSTMENT);
        $adjustment->setType(AdjustmentInterface::TAX_ADJUSTMENT)->shouldBeCalled();

        $dto->getAmount()->willReturn(123);
        $adjustment->setAmount(123)->shouldBeCalled();

        $dto->isNeutral()->willReturn(true);
        $adjustment->setNeutral(true)->shouldBeCalled();

        $dto->getDescription()->willReturn('desc');
        $adjustment->setDescription('desc')->shouldBeCalled();

        $dto->getOriginId()->willReturn(234);
        $adjustment->setOriginId(234)->shouldBeCalled();

        $dto->getOriginType()->willReturn('type of origin');
        $adjustment->setOriginType('type of origin')->shouldBeCalled();

        $dto->getOrder()->willReturn($order);
        $adjustment->setOrder($order)->shouldBeCalled();

        $adjustment->setOrderItem()->shouldNotBeCalled();
        $adjustment->setInventoryUnit()->shouldNotBeCalled();

        $entityManager->persist($adjustment)->shouldBeCalled();
        $entityManager->flush()->shouldNotBeCalled();

        $this->addOnOrderLevel($event);
    }

    function it_add_adjustment_on_order_item_level(
        $adjustmentRepository,
        $entityManager,
        GenericEvent $event,
        AdjustmentDTO $dto,
        AdjustmentInterface $adjustment,
        OrderItemInterface $orderItem,
        OrderInterface $order
    ) {
        $event->getSubject()->shouldBeCalled()->willReturn($dto);

        $adjustmentRepository->createNew()->shouldBeCalled()->willReturn($adjustment);

        $dto->getType()->willReturn($type = AdjustmentInterface::PROMOTION_ADJUSTMENT);
        $adjustment->setType($type)->shouldBeCalled();

        $dto->getAmount()->willReturn(123);
        $adjustment->setAmount(123)->shouldBeCalled();

        $dto->isNeutral()->willReturn(true);
        $adjustment->setNeutral(true)->shouldBeCalled();

        $dto->getDescription()->willReturn('desc');
        $adjustment->setDescription('desc')->shouldBeCalled();

        $dto->getOriginId()->willReturn(234);
        $adjustment->setOriginId(234)->shouldBeCalled();

        $dto->getOriginType()->willReturn('type of origin');
        $adjustment->setOriginType('type of origin')->shouldBeCalled();

        $dto->getInventoryUnit()->willReturn(13);
        $adjustment->setInventoryUnit(13)->shouldBeCalled();

        $dto->getOrder()->willReturn($order);
        $adjustment->setOrder($order)->shouldBeCalled();

        $dto->getOrderItem()->willReturn($orderItem);
        $adjustment->setOrderItem($orderItem)->shouldBeCalled();

        $entityManager->persist($adjustment)->shouldBeCalled();
        $entityManager->flush()->shouldNotBeCalled();

        $this->addOnInventoryUnitLevel($event);
    }
}
