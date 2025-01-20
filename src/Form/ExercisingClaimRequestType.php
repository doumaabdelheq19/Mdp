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

class ExercisingClaimRequestType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accountantName', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom_du_responsable'
                ],
                'label' => 'nom_du_responsable',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('accountantEmail', EmailType::class, [
                'attr' => [
                    'placeholder' => 'email_du_responsable'
                ],
                'label' => 'email_du_responsable',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('requestDate', TextType::class, [
                'attr' => [
                    'placeholder' => '__/__/____',
                    'data-mask' => '00/00/0000',
                    'data-mask-clearifnotmatch' => 'true'
                ],
                'label' => "date_de_la_demande",
                'required' => true,
                'translation_domain' => 'messages',
                'mapped' => false
            ])
            ->add('rights', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'droit'
                ],
                'label' => 'droit',
                'choices' => [
                    "accs" => "acces",
                    "rectification" => "rectification",
                    "effacement" => "effacement",
                    "limitation" => "limitation",
                    "portabilit" => "portabilite",
                    "opposition" => "opposition",
                ],
                'expanded' => false,
                'multiple' => false,
                'translation_domain' => 'messages',
                'required' => true,
            ])
            ->add('customer', TextType::class, [
                'attr' => [
                    'placeholder' => 'personne_concerne'
                ],
                'label' => 'personne_concerne',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('answerDate', TextType::class, [
                'attr' => [
                    'placeholder' => '__/__/____',
                    'data-mask' => '00/00/0000',
                    'data-mask-clearifnotmatch' => 'true'
                ],
                'label' => "date_de_la_rponse",
                'required' => false,
                'translation_domain' => 'messages',
                'mapped' => false
            ])
            ->add('precisions', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'precisions_sur_la_demande'
                ],
                'label' => 'precisions_sur_la_demande',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('documentFile', FileType::class, [
                'attr' => [
                    'placeholder' => 'Joindre un document'
                ],
                'label' => 'Joindre un document',
                'required' => false,
                'mapped' => false
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\ExercisingClaimRequest'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_exercising_claim_request';
    }

}
