<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\OrderBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\OrderBundle\SyliusAdjustmentEvents;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Order\DTO\AdjustmentDTO;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AdjustmentSubscriber implements EventSubscriberInterface
{
    /** @var RepositoryInterface  */
    private $adjustmentRepository;

    /** @var RepositoryInterface  */
    private $orderItemRepository;

    /** @var RepositoryInterface  */
    private $orderRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        RepositoryInterface $adjustmentRepository,
        RepositoryInterface $orderRepository,
        RepositoryInterface $orderItemRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->adjustmentRepository = $adjustmentRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            SyliusAdjustmentEvents::ORDER_LEVEL_ADJUSTMENT => 'addOnOrderLevel',
            SyliusAdjustmentEvents::INVENTORY_UNIT_LEVEL_ADJUSTMENT => 'addOnInventoryUnitLevel',
        );
    }

    /**
     * @param GenericEvent $adjustmentEvent
     */
    public function addOnOrderLevel(GenericEvent $adjustmentEvent)
    {
        /** @var AdjustmentDTO $adjustmentDTO */
        $adjustmentDTO = $adjustmentEvent->getSubject();

        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->createAdjustmentWithCommonValues($adjustmentDTO);

        $adjustment->setOrder($adjustmentDTO->getOrder());

        $this->entityManager->persist($adjustment);
    }

    /**
     * @param GenericEvent $adjustmentEvent
     */
    public function addOnInventoryUnitLevel(GenericEvent $adjustmentEvent)
    {
        /** @var AdjustmentDTO $adjustmentDTO */
        $adjustmentDTO = $adjustmentEvent->getSubject();
        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->createAdjustmentWithCommonValues($adjustmentDTO);

        $adjustment->setOrder($adjustmentDTO->getOrder());
        $adjustment->setOrderItem($adjustmentDTO->getOrderItem());
        $adjustment->setInventoryUnit($adjustmentDTO->getInventoryUnit());

        $this->entityManager->persist($adjustment);
    }

    private function createAdjustmentWithCommonValues(AdjustmentDTO $adjustmentDTO)
    {
        $adjustment = $this->adjustmentRepository->createNew();

        $adjustment->setType($adjustmentDTO->getType());
        $adjustment->setAmount($adjustmentDTO->getAmount());
        $adjustment->setNeutral($adjustmentDTO->isNeutral());
        $adjustment->setDescription($adjustmentDTO->getDescription());
        $adjustment->setOriginId($adjustmentDTO->getOriginId());
        $adjustment->setOriginType($adjustmentDTO->getOriginType());

        return $adjustment;
    }

}