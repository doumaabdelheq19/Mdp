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

class ActionType extends AbstractType
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
            ->add('budget', TextType::class, [
                'attr' => [
                    'placeholder' => 'budget_en_euros'
                ],
                'label' => 'budget_en_euros',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('accountantFirstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'prnom'
                ],
                'label' => 'prnom',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('accountantLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom'
                ],
                'label' => 'nom',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('accountantPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('accountantEmail', TextType::class, [
                'attr' => [
                    'placeholder' => 'email'
                ],
                'label' => 'email',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('goal', TextType::class, [
                'attr' => [
                    'placeholder' => 'objectif'
                ],
                'label' => 'objectif',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('information', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'informations_complmentaires',
                    "rows" => 6
                ],
                'label' => 'informations_complmentaires',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('usefulLink', TextType::class, [
                'attr' => [
                    'placeholder' => 'lien_utile'
                ],
                'label' => 'lien_utile',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('setUpDate', DateType::class, [
                'attr' => [
                    'placeholder' => 'date_de_mise_en_place_souhaite'
                ],
                'label' => 'date_de_mise_en_place_souhaite',
                'widget' => 'single_text',
                'html5' => false,
                'required' => false,
                'translation_domain' => 'messages',
                'empty_data' => '',
            ])
            ->add('terminated', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'realise'
                ],
                'label' => 'realise',
                'choices' => [
                    "oui" => "1",
                    "non" => "0",
                ],
                'data' => '0',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('priority', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Priorité'
                ],
                'label' => 'Priorité',
                'choices' => [
                    "Urgente" => "1",
                    "Modérée" => "2",
                    "Faible" => "3"
                ],
                'expanded' => true,
                'multiple' => false,
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
            'data_class' => 'App\Entity\Action'
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
