<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('address', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name', 
                'label' => 'City',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paidOnDelivery', null, [
                'label' => 'Pay on Delivery',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}