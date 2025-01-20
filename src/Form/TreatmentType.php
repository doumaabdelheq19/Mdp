<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use App\Entity\Conformity;
use App\Entity\TreatmentState;
use App\Entity\TreatmentStdCategory;
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

class TreatmentType extends AbstractType
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
            ->add('mainPurpose', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'finalit_principale',
                ],
                'label' => 'finalit_principale',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('purpose1', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'sousfinalit_1'
                ],
                'label' => 'sousfinalit_1',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('purpose2', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'sousfinalit_2'
                ],
                'label' => 'sousfinalit_2',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('purpose3', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'sousfinalit_3'
                ],
                'label' => 'sousfinalit_3',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('purpose4', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'sousfinalit_4'
                ],
                'label' => 'sousfinalit_4',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('purpose5', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'sousfinalit_5'
                ],
                'label' => 'sousfinalit_5',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('othersPurpose', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'autres_finalits'
                ],
                'label' => 'autres_finalits',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'description_du_traitement'
                ],
                'label' => 'description_du_traitement',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('peopleData', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'catgories_des_personnes_concernes'
                ],
                'label' => 'catgories_des_personnes_concernes',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('transferOutsideUeCountries', TextType::class, [
                'attr' => [
                    'placeholder' => 'pays'
                ],
                'label' => 'pays',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('consentAsked', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'le_consentement_estil_demand'
                ],
                'label' => 'le_consentement_estil_demand',
                'choices' => [
                    "oui" => "1",
                    "non" => "0",
                ],
                'empty_data' => '0',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('consentHow', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'si_oui_comment'
                ],
                'label' => 'si_oui_comment',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('piaCriteria', ChoiceType::class, [
                'label' => 'cochez_les_cases_correspondantes',
                'choices' => [
                    "donnees_sensibles_ou_hautement_personnelles" => "1",
                    "personnes_vulnerables" => "2",
                    "profilage_et_valuation" => "3",
                    "prise_de_dcision_automatise_avec_effet_lgal_ou_sim" => "4",
                    "grande_chelle" => "5",
                    "surveillance_systmatique" => "6",
                    "exclusion_du_benefice_d_un_droit_ou_d_un_contrat" => "7",
                    "usages_innovants" => "8",
                    "croisement_de_donnes" => "9",
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('piaFileFile', FileType::class, [
                'label' => "fichier_pia",
                'required' => false,
                'data_class' => null,
                'mapped' => false,
                'translation_domain' => 'messages',
            ])
            ->add('legalBasis', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'base_juridique_du_traitement'
                ],
                'label' => 'base_juridique_du_traitement',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('dataSource', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'source_des_donnes'
                ],
                'label' => 'source_des_donnes',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            /*->add('automatedDecision', CheckboxType::class, [
                'label' => "Prise de décision automatisée",
                'required' => false
            ])*/
            ->add('dataRetentionPeriod', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'dure_de_conservation'
                ],
                'label' => 'dure_de_conservation',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('treatmentAccountant', TextType::class, [
                'attr' => [
                    'placeholder' => 'responsable_de_traitement'
                ],
                'label' => 'responsable_de_traitement',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('dpo', TextType::class, [
                'attr' => [
                    'placeholder' => 'dpo'
                ],
                'label' => 'dpo',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('serviceAccountant', TextType::class, [
                'attr' => [
                    'placeholder' => 'responsable_du_service_concern'
                ],
                'label' => 'responsable_du_service_concern',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('editor', TextType::class, [
                'attr' => [
                    'placeholder' => 'rdacteur_de_la_fiche'
                ],
                'label' => 'rdacteur_de_la_fiche',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('state', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'etat_du_traitement',
                ),
                'label' => 'le_traitement_est_il_finalise',
                'required' => true,
                'class' => TreatmentState::class,
                'expanded' => true,
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
            'data_class' => 'App\Entity\Treatment'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_treatment';
    }

}
