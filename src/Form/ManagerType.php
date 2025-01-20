<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use App\Entity\Conformity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ManagerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('companyName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Société*'
                ],
                'label' => 'Société*',
                'required' => true,
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'placeholder' => 'Adresse*'
                ],
                'label' => 'Adresse*',
                'required' => true,
            ])
            ->add('address2', TextType::class, [
                'attr' => [
                    'placeholder' => 'Complément'
                ],
                'label' => 'Complément',
                'required' => false,
            ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'Code postal*'
                ],
                'label' => 'Code postal*',
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'placeholder' => 'Ville*'
                ],
                'label' => 'Ville*',
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => [
                    'placeholder' => 'Téléphone*'
                ],
                'label' => 'Téléphone*',
                'required' => true,
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'placeholder' => 'Email* (identifiant de connexion)'
                ],
                'label' => 'Email* (identifiant de connexion)',
                'required' => true,
                'mapped' => false
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Prénom*'
                ],
                'label' => 'Prénom*',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom*'
                ],
                'label' => 'Nom*',
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
            'data_class' => 'App\Entity\Manager'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_manager';
    }

}
