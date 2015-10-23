<?php
namespace Sylius\Bundle\OrderBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Money\Currency;

abstract class IntegrationTestCase extends WebTestCase
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ContainerInterface */
    protected $container;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  */
    protected $client;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->eventDispatcher = $this->container->get('event_dispatcher');

        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->entityManager->beginTransaction();
    }

    public function tearDown()
    {
        $this->entityManager->rollback();

        parent::tearDown();
    }

    /**
     * @return OrderInterface
     */
    protected function createOrder()
    {
        /** @var OrderInterface $order */
        $order = $this->container->get('sylius.repository.order')->createNew();
        $order->setCurrency(new Currency('USD'));

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @return \Sylius\Component\Core\Model\OrderItemInterface
     */
    protected function createOrderItemThatBelongTo(OrderInterface $order)
    {
        $orderItem = $this->createOrderItem();

        $orderItem->setOrder($order);
        $order->addItem($orderItem);
        $orderItem->setOrder($order);

        $this->entityManager->persist($order);
        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return $orderItem;
    }

    /**
     * @return \Sylius\Component\Core\Model\OrderItemInterface
     */
    protected function createOrderItem()
    {
        /** @var Taxonomy $taxonomy */
        $taxonomy = $this->container->get('sylius.repository.taxonomy')->createNew();
        $taxonomy->setName('taxonomy-name');
        $taxonomy->setCurrentLocale($this->container->getParameter('sylius.locale'));

        /** @var TaxonInterface $taxon */
        $taxon = $this->container->get('sylius.repository.taxon')->createNew();
        $taxon->setName('taxon-name');

        /** @var ProductInterface $product */
        $product = $this->container->get('sylius.repository.product')->createNew();
        $product->setDescription('product-description');
        $product->setName('product-name');
        $product->addTaxon($taxon);

        /** @var ProductVariant $productVariant */
        $productVariant = $this->container->get('sylius.repository.product_variant')->createNew();
        $productVariant->setPrice(123);
        $productVariant->setProduct($product);
        $product->setMasterVariant($productVariant);
        $product->setPrice(123);


        /** @var \Sylius\Component\Core\Model\OrderItemInterface $orderItem */
        $orderItem = $this->container->get('sylius.repository.order_item')->createNew();
        $orderItem->setVariant($product->getMasterVariant());

        $priceCalculator = $this->container->get('sylius.price_calculator');
        $orderItem->setUnitPrice($priceCalculator->calculate($product->getMasterVariant()));

        $this->entityManager->persist($taxonomy);
        $this->entityManager->persist($taxon);
        $this->entityManager->persist($product);
        $this->entityManager->persist($productVariant);
        $this->entityManager->flush();

        return $orderItem;
    }
}