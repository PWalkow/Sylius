<?php
/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Component\Order\DTO;




class AdjustmentDTO
{
    /** @var int */
    private $orderId;

    /** @var int */
    private $orderItemId;

    /** @var  int */
    private $inventoryUnit;

    /** @var  string */
    private $type;

    /** @var  string */
    private $description;

    /** @var  int */
    private $amount;

    /** @var boolean */
    private $neutral;

    /** @var  int */
    private $originId;

    /** @var  int */
    private $originType;

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int
     */
    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    /**
     * @param int $orderItemId
     */
    public function setOrderItemId($orderItemId)
    {
        $this->orderItemId = $orderItemId;
    }

    /**
     * @return int
     */
    public function getInventoryUnit()
    {
        return $this->inventoryUnit;
    }

    /**
     * @param int $inventoryUnit
     */
    public function setInventoryUnit($inventoryUnit)
    {
        $this->inventoryUnit = $inventoryUnit;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return boolean
     */
    public function isNeutral()
    {
        return $this->neutral;
    }

    /**
     * @param boolean $neutrality
     */
    public function setNeutral($neutrality = true)
    {
        $this->neutral = $neutrality;
    }

    /**
     * @return int
     */
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param int $originId
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;
    }

    /**
     * @return int
     */
    public function getOriginType()
    {
        return $this->originType;
    }

    /**
     * @param int $originType
     */
    public function setOriginType($originType)
    {
        $this->originType = $originType;
    }

}