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

class SubcontractorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom_std'
                ],
                'label' => 'nom_std',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('type', TextType::class, [
                'attr' => [
                    'placeholder' => 'typologie'
                ],
                'label' => 'typologie',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('contactFirstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'prnom'
                ],
                'label' => 'prnom',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('contactLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom'
                ],
                'label' => 'nom',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('contactPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('contactEmail', TextType::class, [
                'attr' => [
                    'placeholder' => 'email'
                ],
                'label' => 'email',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('privacyPolicyLink', TextType::class, [
                'attr' => [
                    'placeholder' => 'https_base'
                ],
                'label' => 'lien_vers_la_politique_de_confidentialite',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('conformity', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'conformit',
                ),
                'label' => 'conformit',
                'placeholder' => 'conformit',
                'required' => true,
                'translation_domain' => 'messages',
                'class' => Conformity::class,
            ])
            ->add('subcontractorType', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Type de sous-traitance',
                ),
                'label' => 'Type de sous-traitance',
                'required' => true,
                'translation_domain' => 'messages',
                'class' => \App\Entity\SubcontractorType::class,
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Subcontractor'
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
