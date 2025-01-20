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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Client',
                ),
                'label' => 'Client',
                'placeholder' => 'Client',
                'required' => true,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    return $qb->orderBy('u.companyName', 'ASC');
                },
                'choice_label' => function(User $user) {
                    return $user->getCompanyName();
                },
            ])
            ->add('beginDate', DateType::class, [
                'attr' => [
                    'placeholder' => 'Date'
                ],
                'widget' => 'single_text',
                'html5' => false,
                'required' => false,
                'empty_data' => '',
            ])
            ->add('billingType', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Type de facturation'
                ],
                'label' => 'Type de facturation',
                'choices' => [
                    "Au mois" => "m",
                    "A l'année" => "y",
                ],
                'required' => true,
                'mapped' => false
            ])
            ->add('offer', TextType::class, [
                'attr' => [
                    'placeholder' => 'Offre souscrite'
                ],
                'label' => 'Offre souscrite',
                'required' => true,
            ])
            ->add('billing', TextType::class, [
                'attr' => [
                    'placeholder' => 'Facturation'
                ],
                'label' => 'Facturation',
                'required' => true,
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
