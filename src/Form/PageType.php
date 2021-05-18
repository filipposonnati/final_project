<?php

namespace App\Form;

use App\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Inserisci il titolo'
                ]
            ])
            ->add('introduction', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Inserisci l\'introduzione'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'choices' => $options['categories']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'categories' => ['Nessuna categoria' => null],
        ]);

        $resolver->setAllowedTypes('categories', 'array');
    }
}
