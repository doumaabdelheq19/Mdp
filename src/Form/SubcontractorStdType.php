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

class SubcontractorStdType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom*'
                ],
                'label' => 'Nom*',
                'required' => true,
            ])
            ->add('type', TextType::class, [
                'attr' => [
                    'placeholder' => 'Typologie*'
                ],
                'label' => 'Typologie*',
                'required' => true,
            ])
            ->add('contactFirstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Prénom'
                ],
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('contactLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom'
                ],
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('contactPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'Téléphone'
                ],
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('contactEmail', TextType::class, [
                'attr' => [
                    'placeholder' => 'Email'
                ],
                'label' => 'Email',
                'required' => false,
            ])
            ->add('privacyPolicyLink', TextType::class, [
                'attr' => [
                    'placeholder' => 'https://'
                ],
                'label' => 'Lien vers la politique de confidentialité ',
                'required' => false,
            ])
            ->add('conformity', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Conformité',
                ),
                'label' => 'Conformité',
                'placeholder' => 'Conformité',
                'required' => true,
                'class' => Conformity::class,
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\SubcontractorStd'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_subcontractor';
    }

}
