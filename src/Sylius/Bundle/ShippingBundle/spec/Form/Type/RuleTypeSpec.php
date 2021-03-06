<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Bundle\ShippingBundle\Form\Type;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Saša Stamenković <umpirsky@gmail.com>
 */
class RuleTypeSpec extends ObjectBehavior
{
    function let(ServiceRegistryInterface $checkerRegistry)
    {
        $this->beConstructedWith('Rule', array('sylius'), $checkerRegistry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Bundle\ShippingBundle\Form\Type\RuleType');
    }

    function it_is_a_form_type()
    {
        $this->shouldHaveType('Symfony\Component\Form\AbstractType');
    }

    function it_should_build_form_with_rule_choice_field(
        FormBuilder $builder,
        FormFactoryInterface $factory
    ) {
        $builder
            ->getFormFactory()
            ->willReturn($factory)
        ;

        $builder
            ->addEventSubscriber(
                Argument::type('Sylius\Bundle\ShippingBundle\Form\EventListener\BuildRuleFormSubscriber')
            )
            ->willReturn($builder)
        ;

        $builder
            ->add('type', 'sylius_shipping_rule_choice', Argument::any())
            ->willReturn($builder)
        ;

        $this->buildForm($builder, array());
    }

    function it_should_add_rule_event_subscriber(
        FormBuilder $builder,
        FormFactoryInterface $factory
    ) {
        $builder
            ->getFormFactory()
            ->willReturn($factory)
        ;

        $builder
            ->add(Argument::any(), Argument::cetera())
            ->willReturn($builder)
        ;

        $builder
            ->addEventSubscriber(Argument::type('Sylius\Bundle\ShippingBundle\Form\EventListener\BuildRuleFormSubscriber'))
            ->willReturn($builder)
        ;

        $this->buildForm($builder, array());
    }

    function it_should_define_assigned_data_class(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class'        => 'Rule',
                'validation_groups' => array('sylius'),
            ))
            ->shouldBeCalled()
        ;

        $this->configureOptions($resolver);
    }
}
