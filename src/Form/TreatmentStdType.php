<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use App\Entity\TreatmentStdCategory;
use Doctrine\ORM\EntityRepository;
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

class TreatmentStdType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('category', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Catégorie',
                ),
                'label' => 'Catégorie',
                'placeholder' => 'Catégorie',
                'required' => true,
                'class' => TreatmentStdCategory::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    return $qb
                        ->addSelect('(CASE WHEN c.id = 17 THEN 1 ELSE 0 END) AS HIDDEN ordCol')
                        ->addOrderBy('ordCol', 'ASC')
                        ->addOrderBy('c.libelle', 'ASC');
                },
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom*'
                ],
                'label' => 'Nom*',
                'required' => true,
            ])
            ->add('mainPurpose', TextType::class, [
                'attr' => [
                    'placeholder' => 'Finalité principale*'
                ],
                'label' => 'Finalité principale*',
                'required' => true,
            ])
            ->add('purpose1', TextType::class, [
                'attr' => [
                    'placeholder' => 'Sous-finalité 1'
                ],
                'label' => 'Sous-finalité 1',
                'required' => false,
            ])
            ->add('purpose2', TextType::class, [
                'attr' => [
                    'placeholder' => 'Sous-finalité 2'
                ],
                'label' => 'Sous-finalité 2',
                'required' => false,
            ])
            ->add('purpose3', TextType::class, [
                'attr' => [
                    'placeholder' => 'Sous-finalité 3'
                ],
                'label' => 'Sous-finalité 3',
                'required' => false,
            ])
            ->add('purpose4', TextType::class, [
                'attr' => [
                    'placeholder' => 'Sous-finalité 4'
                ],
                'label' => 'Sous-finalité 4',
                'required' => false,
            ])
            ->add('purpose5', TextType::class, [
                'attr' => [
                    'placeholder' => 'Sous-finalité 5'
                ],
                'label' => 'Sous-finalité 5',
                'required' => false,
            ])
            ->add('othersPurpose', TextType::class, [
                'attr' => [
                    'placeholder' => 'Autres Finalités'
                ],
                'label' => 'Autres Finalités',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Décrire le traitement'
                ],
                'label' => 'Décrire le traitement',
                'required' => false,
            ])
            ->add('peopleData', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Catégories des personnes concernées'
                ],
                'label' => 'Catégories des personnes concernées',
                'required' => false,
            ])
            ->add('transferOutsideUeCountries', TextType::class, [
                'attr' => [
                    'placeholder' => 'Pays'
                ],
                'label' => 'Pays',
                'required' => false,
            ])
            ->add('consentAsked', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Le consentement est-il demandé ?'
                ],
                'label' => 'Le consentement est-il demandé ?',
                'choices' => [
                    "Oui" => "1",
                    "Non" => "0",
                ],
                'empty_data' => '0',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('consentHow', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Si oui, comment ?'
                ],
                'label' => 'Si oui, comment ?',
                'required' => false,
            ])
            ->add('legalBasis', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Base juridique du traitement'
                ],
                'label' => 'Base juridique du traitement',
                'required' => false,
            ])
            ->add('piaCriteria', ChoiceType::class, [
                'label' => 'Cochez les cases correspondantes',
                'choices' => [
                    "Données sensibles ou hautement personnelles" => "1",
                    "Personnes vulnérables" => "2",
                    "Profilage et évaluation" => "3",
                    "Prise de décision automatisée avec effet légal ou similaire" => "4",
                    "Grande échelle" => "5",
                    "Surveillance systématique" => "6",
                    "Exclusion du bénéfice d'un droit ou d'un contrat" => "7",
                    "Usages innovants" => "8",
                    "Croisement de données" => "9",
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('dataSource', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Source des données'
                ],
                'label' => 'Source des données',
                'required' => false,
            ])
            /*->add('automatedDecision', CheckboxType::class, [
                'label' => "Prise de décision automatisée",
                'required' => false
            ])*/
            ->add('dataRetentionPeriod', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Durée de conservation'
                ],
                'label' => 'Durée de conservation',
                'required' => false,
            ])
            ->add('piaExoneration', CheckboxType::class, [
                'label' => "Cas d'éxonération de réalisation de PIA ?",
                'required' => false
            ])
            ->add('insufficientCriteria', CheckboxType::class, [
                'label' => "Absence de critère suffisant",
                'required' => false
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\TreatmentStd'
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
