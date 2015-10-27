<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Component\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Order\Model\AdjustableInterface;
use Sylius\Component\Order\Model\AdjustmentInterface;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class InventoryUnit implements InventoryUnitInterface, AdjustableInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var StockableInterface
     */
    protected $stockable;

    /**
     * @var string
     */
    protected $inventoryState = InventoryUnitInterface::STATE_CHECKOUT;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var Collection[AdjustmentInterface]
     */
    protected $adjustments;

    /** @var int */
    protected $adjustmentsTotal;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->adjustments = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getStockable()
    {
        return $this->stockable;
    }

    /**
     * {@inheritdoc}
     */
    public function setStockable(StockableInterface $stockable)
    {
        $this->stockable = $stockable;
    }

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return $this->stockable->getSku();
    }

    /**
     * {@inheritdoc}
     */
    public function getInventoryName()
    {
        return $this->stockable->getInventoryName();
    }

    /**
     * {@inheritdoc}
     */
    public function getInventoryState()
    {
        return $this->inventoryState;
    }

    /**
     * {@inheritdoc}
     */
    public function setInventoryState($state)
    {
        $this->inventoryState = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function isSold()
    {
        return InventoryUnitInterface::STATE_SOLD === $this->inventoryState;
    }

    /**
     * {@inheritdoc}
     */
    public function isBackordered()
    {
        return InventoryUnitInterface::STATE_BACKORDERED === $this->inventoryState;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(\DateTime $createAt)
    {
        $this->createdAt = $createAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @inheritDoc
     */
    public function getAdjustments($type = null)
    {
        return $this->adjustments;
    }

    /**
     * @inheritDoc
     */
    public function addAdjustment(AdjustmentInterface $adjustment)
    {
        $this->adjustments->add($adjustment);
        $adjustment->setAdjustable($this);
    }

    /**
     * @inheritDoc
     */
    public function removeAdjustment(AdjustmentInterface $adjustment)
    {
        $this->adjustments->removeElement($adjustment);
        $adjustment->setAdjustable(null);
    }

    /**
     * @inheritDoc
     */
    public function getAdjustmentsTotal($type = null)
    {
        if (null === $type) {
            return $this->adjustmentsTotal;
        }

        $total = 0;
        foreach ($this->getAdjustments($type) as $adjustment) {
            $total += $adjustment->getAmount();
        }

        return $total;
    }

    /**
     * @inheritDoc
     */
    public function removeAdjustments($type)
    {
        foreach ($this->getAdjustments($type) as $adjustment) {
            if ($adjustment->isLocked()) {
                continue;
            }

            $this->removeAdjustments($adjustment);
        }
    }

    /**
     * @inheritDoc
     */
    public function clearAdjustments()
    {
        $this->adjustments->clear();
    }

    /**
     * @inheritDoc
     */
    public function calculateAdjustmentsTotal()
    {
        $this->adjustmentsTotal = 0;

        foreach ($this->getAdjustments() as $adjustment) {
            if ($adjustment->isNeutral()) {
                continue;
            }

            $this->adjustmentsTotal += $adjustment->getAmount();
        }
    }

    public function hasAdjustment(AdjustmentInterface $adjustment)
    {
        return $this->getAdjustments()->contains($adjustment);
    }
}
