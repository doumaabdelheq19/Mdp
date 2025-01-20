<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InfoType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Titre*'
                ],
                'label' => 'Titre*',
                'required' => true,
            ])
            ->add('date', DateType::class, [
                'attr' => [
                    'placeholder' => 'Date*'
                ],
                'label' => 'Date*',
                'widget' => 'single_text',
                'html5' => false,
                'required' => true,
                'empty_data' => '',
            ])
            ->add('link', TextType::class, [
                'attr' => [
                    'placeholder' => 'https://'
                ],
                'label' => 'Lien',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Contenu',
                    "rows" => 6
                ],
                'label' => 'Contenu',
                'required' => false,
            ])
            ->add('filePicture', FileType::class, [
                'attr' => [
                    'placeholder' => 'Image'
                ],
                'label' => 'Image',
                'required' => false,
                'mapped' => false
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => "Active",
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
            'data_class' => 'App\Entity\Info'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_info';
    }

}
