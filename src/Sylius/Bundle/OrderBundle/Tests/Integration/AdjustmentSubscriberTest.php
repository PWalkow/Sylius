<?php

namespace Sylius\Bundle\OrderBundle\Tests\Integration;

use Sylius\Bundle\OrderBundle\SyliusAdjustmentEvents;
use Sylius\Bundle\OrderBundle\Tests\IntegrationTestCase;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Order\DTO\AdjustmentDTO;
use Sylius\Component\Order\Model\Adjustment;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class AdjustmentSubscriberTest
 * @package Sylius\Bundle\OrderBundle\Tests\Integration
 * @author  Piotr Walków <walkow.piotr@gmail.com>
 *
 * @group Integration
 */
class AdjustmentSubscriberTest extends IntegrationTestCase
{
    /** @test */
    public function creation_order_level_adjustment()
    {
        $order = $this->createOrder();

        $adjustmentDTO = new AdjustmentDTO();
        $adjustmentDTO->setType(AdjustmentInterface::TAX_ADJUSTMENT);
        $adjustmentDTO->setAmount(123);
        $adjustmentDTO->setDescription('desc');
        $adjustmentDTO->setOrderId($order->getId());
        $adjustmentDTO->setNeutral();

        $this->eventDispatcher->dispatch(
            SyliusAdjustmentEvents::ORDER_LEVEL_ADJUSTMENT,
            new GenericEvent($adjustmentDTO)
        );

        $this->entityManager->flush();

        $adjustmentRepository = $this->entityManager->getRepository(Adjustment::class);
        $adjustments = $adjustmentRepository->findAll();

        $this->assertCount(1, $adjustments);
        $adjustment = $adjustments[0];

        $this->assertSame($order, $adjustment->getOrder());
        $this->assertSame(123, $adjustment->getAmount());
        $this->assertSame(AdjustmentInterface::TAX_ADJUSTMENT, $adjustment->getType());
        $this->assertSame('desc', $adjustment->getDescription());
        $this->assertSame(true, $adjustment->isNeutral());
    }

    /** @test */
    public function creation_inventory_unit_level_adjustments()
    {
        $order = $this->createOrder();
        $orderItem = $this->createOrderItemThatBelongTo($order);

        $adjustmentDTO = new AdjustmentDTO();
        $adjustmentDTO->setType(AdjustmentInterface::PROMOTION_ADJUSTMENT);
        $adjustmentDTO->setAmount(20000);
        $adjustmentDTO->setDescription('aaa');
        $adjustmentDTO->setOrderId($order->getId());
        $adjustmentDTO->setOrderItemId($orderItem->getId());
        $adjustmentDTO->setInventoryUnit(1);

        $adjustmentDTO2 = new AdjustmentDTO();
        $adjustmentDTO2->setType(AdjustmentInterface::PROMOTION_ADJUSTMENT);
        $adjustmentDTO2->setAmount(40000);
        $adjustmentDTO2->setDescription('bbb');
        $adjustmentDTO2->setOrderId($order->getId());
        $adjustmentDTO2->setOrderItemId($orderItem->getId());
        $adjustmentDTO2->setInventoryUnit(2);

        $this->eventDispatcher->dispatch(
            SyliusAdjustmentEvents::INVENTORY_UNIT_LEVEL_ADJUSTMENT,
            new GenericEvent($adjustmentDTO)
        );

        $this->eventDispatcher->dispatch(
            SyliusAdjustmentEvents::INVENTORY_UNIT_LEVEL_ADJUSTMENT,
            new GenericEvent($adjustmentDTO2)
        );

        $this->entityManager->flush();

        $adjustmentRepository = $this->entityManager->getRepository(Adjustment::class);

        $adjustments = $adjustmentRepository->findAll();

        $this->assertCount(2, $adjustments);
        $adjustment = $adjustments[0];

        $this->assertEquals($order, $adjustment->getOrder());
        $this->assertEquals($orderItem, $adjustment->getOrderItem());
        $this->assertSame(AdjustmentInterface::PROMOTION_ADJUSTMENT, $adjustment->getType());
        $this->assertSame(20000, $adjustment->getAmount());
        $this->assertSame('aaa', $adjustment->getDescription());
        $this->assertSame(1, $adjustment->getInventoryUnit());

        $adjustment2 = $adjustments[1];
        $this->assertEquals($order, $adjustment2->getOrder());
        $this->assertEquals($orderItem, $adjustment2->getOrderItem());
        $this->assertSame(AdjustmentInterface::PROMOTION_ADJUSTMENT, $adjustment2->getType());
        $this->assertSame(40000, $adjustment2->getAmount());
        $this->assertSame('bbb', $adjustment2->getDescription());
        $this->assertSame(2, $adjustment2->getInventoryUnit());
    }

}