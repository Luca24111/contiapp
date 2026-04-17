<?php

namespace App\Form;

use App\Entity\Category;
use App\Enum\TransactionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome categoria',
                'attr' => [
                    'placeholder' => 'Es. Stipendio, Spesa alimentare, Trasporti',
                ],
            ])
            ->add('type', EnumType::class, [
                'class' => TransactionType::class,
                'label' => 'Tipologia',
                'choice_label' => static fn (TransactionType $choice) => $choice->label(),
            ])
            ->add('color', ColorType::class, [
                'label' => 'Colore',
            ])
            ->add('icon', TextType::class, [
                'label' => 'Icona',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Es. wallet, briefcase, cart',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
