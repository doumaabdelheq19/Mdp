<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use App\Entity\Manager;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionUserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = new \DateTime('now');

        $builder
            ->add('beginDate', TextType::class, [
                'attr' => [
                    'placeholder' => '__/__/____',
                    'data-mask' => '00/00/0000',
                    'data-mask-clearifnotmatch' => 'true'
                ],
                'label' => 'Date de début',
                'required' => true,
                'mapped' => false,
                'data' => $now->format("d/m/Y")
            ])
            ->add('subscriptionType', TextType::class, [
                'label' => 'Abonnement',
                'mapped' => false,
                'disabled' => true
            ])
            ->add('offer', TextType::class, [
                'attr' => [
                    'placeholder' => 'Informations abonnement'
                ],
                'label' => 'Informations abonnement',
                "required" => false
            ])
            ->add('billing', TextType::class, [
                'attr' => [
                    'placeholder' => 'Informations facturation'
                ],
                'label' => 'Informations facturation',
                "required" => false
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Subscription'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_subscription';
    }

}
