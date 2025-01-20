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

class ActionStdType extends AbstractType
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
            ->add('budget', TextType::class, [
                'attr' => [
                    'placeholder' => 'Budget en €*'
                ],
                'label' => 'Budget en €*',
                'required' => true,
            ])
            ->add('goal', TextType::class, [
                'attr' => [
                    'placeholder' => 'Objectif*'
                ],
                'label' => 'Objectif*',
                'required' => true,
            ])
            ->add('information', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Informations complémentaires',
                    "rows" => 6
                ],
                'label' => 'Informations complémentaires',
                'required' => false,
            ])
            ->add('usefulLink', TextType::class, [
                'attr' => [
                    'placeholder' => 'Lien utile'
                ],
                'label' => 'Lien utile',
                'required' => false,
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\ActionStd'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_action';
    }

}
