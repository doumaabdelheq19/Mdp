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

class TrainingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("title", TextType::class, [
                "attr" => [
                    "placeholder" => "Titre"
                ],
                "label" => "Titre",
                "required" => true
            ])
            ->add('active', CheckboxType::class, [
                'label' => "Actif",
                'required' => false,
            ])
            ->add('availableForAll', CheckboxType::class, [
                'label' => "Disponible pour tous les clients",
                'required' => false,
            ])
            ->add('pictureFile', FileType::class, [
                'label' => "Image d'entête du questionnaire",
                'required' => false,
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
            'data_class' => 'App\Entity\Training'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_training';
    }

}
