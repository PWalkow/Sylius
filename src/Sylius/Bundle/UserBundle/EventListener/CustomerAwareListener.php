<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\UserBundle\EventListener;

use Sylius\Component\Cart\Event\CartEvent;
use Sylius\Component\Cart\Provider\CartProviderInterface;
use Sylius\Component\Resource\Exception\UnexpectedTypeException;
use Sylius\Component\User\Context\CustomerContextInterface;
use Sylius\Component\User\Model\CustomerAwareInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class CustomerAwareListener
{
    /**
     * @var CustomerContextInterface
     */
    protected $customerContext;

    /**
     * @var CartProviderInterface
     */
    protected $cartProvider;

    /**
     * @param CustomerContextInterface $securityContext
     * @param CartProviderInterface    $cartProvider
     */
    public function __construct(
        CustomerContextInterface $securityContext,
        CartProviderInterface $cartProvider
    )
    {
        $this->customerContext = $securityContext;
        $this->cartProvider = $cartProvider;
    }

    /**
     * @param Event $event
     */
    public function setCustomer(Event $event)
    {
        if ($event instanceof CartEvent) {
            $resource = $event->getCart();
        } else {
            $resource = $event->getSubject();
        }

        if (!$resource instanceof CustomerAwareInterface) {
            throw new UnexpectedTypeException($resource, 'Sylius\Component\User\Model\CustomerAwareInterface');
        }

        if (null === $customer = $this->customerContext->getCustomer()) {
            return;
        }

        $resource->setCustomer($customer);
    }
}
