<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class IncidentViewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('cnilInformed', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'cnil_informe'
                ],
                'label' => 'cnil_informe',
                'choices' => [
                    "oui" => "1",
                    "non" => "0",
                ],
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('notice72H', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'dlai_de_72h'
                ],
                'label' => 'dlai_de_72h',
                'choices' => [
                    "oui" => "1",
                    "non" => "0",
                ],
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('date', DateType::class, [
                'attr' => [
                    'placeholder' => 'date'
                ],
                'widget' => 'single_text',
                'html5' => false,
                'disabled' => true,
                'translation_domain' => 'messages',
                'empty_data' => '',
            ])
            ->add('type', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'nature'
                ],
                'placeholder' => 'nature',
                'choices' => [
                    "consultation_non_autorisee" => "Consultation non autorisée",
                    "modification" => "Modification",
                    "copie_non_autorisee" => "Copie non autorisée",
                    "perte" => "Perte"
                ],
                'label' => 'nature',
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('peopleNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'Ex:<10'
                ],
                'label' => 'nombre_de_personnes',
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('fileType', TextType::class, [
                'attr' => [
                    'placeholder' => 'ex_fichier_client'
                ],
                'label' => 'type_de_fichier',
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('consequences', TextType::class, [
                'attr' => [
                    'placeholder' => 'risque_de_demarchage_p'
                ],
                'label' => 'consquences',
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('takenMeasures', TextType::class, [
                'attr' => [
                    'placeholder' => 'information_des_clients_p'
                ],
                'label' => 'mesures_prises',
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ->add('peopleInformed', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'les_personnes_ont_ete_informees'
                ],
                'label' => 'les_personnes_ont_ete_informees',
                'choices' => [
                    "oui" => "1",
                    "non" => "0",
                ],
                'disabled' => true,
                'translation_domain' => 'messages',
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Incident'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_incident';
    }

}
