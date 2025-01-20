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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('companyName', TextType::class, [
                'attr' => [
                    'placeholder' => 'societe'
                ],
                'label' => 'societe',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('siret', TextType::class, [
                'attr' => [
                    'placeholder' => 'siret'
                ],
                'label' => 'siret',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'placeholder' => 'adresse'
                ],
                'label' => 'adresse',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('address2', TextType::class, [
                'attr' => [
                    'placeholder' => 'complement'
                ],
                'label' => 'complement',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'code_postal'
                ],
                'label' => 'code_postal',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'placeholder' => 'ville'
                ],
                'label' => 'ville',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('companyPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('phone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('employeesNumber', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'nombre_de_salaries'
                ],
                'label' => 'nombre_de_salaries',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('language', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'langue_de_l_interface'
                ],
                'label' => 'langue_de_l_interface',
                'choices' => [
                    "français" => "fr",
                    "anglais" => "en",
                ],
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'placeholder' => 'email_identifiant_de_connexion'
                ],
                'label' => 'email_identifiant_de_connexion',
                'required' => true,
                'translation_domain' => 'messages',
                'mapped' => false
            ])
            ->add('contactFirstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'prnom'
                ],
                'label' => 'prnom',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('contactLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom'
                ],
                'label' => 'nom',
                'required' => true,
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
            ->add('contactPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('contactJob', TextType::class, [
                'attr' => [
                    'placeholder' => 'fonction'
                ],
                'label' => 'fonction',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('accountantFirstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'prnom'
                ],
                'label' => 'prnom',
                'required' => true,
                'translation_domain' => 'messages',
            ])
            ->add('accountantLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'nom'
                ],
                'label' => 'nom',
                'required' => true,
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
            ->add('accountantPhone', TextType::class, [
                'attr' => [
                    'placeholder' => 'tlphone'
                ],
                'label' => 'tlphone',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('accountantJob', TextType::class, [
                'attr' => [
                    'placeholder' => 'fonction'
                ],
                'label' => 'fonction',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('pictureFile', FileType::class, [
                'required' => false,
                'translation_domain' => 'messages',
                'data_class' => null,
                'mapped' => false,
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }

}
