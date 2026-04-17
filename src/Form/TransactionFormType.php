<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Enum\TransactionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<Category> $categories */
        $categories = $options['categories'];

        $builder
            ->add('type', EnumType::class, [
                'class' => TransactionType::class,
                'label' => 'Tipologia',
                'choice_label' => static fn (TransactionType $choice) => $choice->label(),
                'attr' => [
                    'data-role' => 'transaction-type',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choices' => $categories,
                'label' => 'Categoria',
                'placeholder' => 'Seleziona una categoria',
                'choice_label' => static fn (Category $category) => $category->getName(),
                'choice_attr' => static fn (?Category $category): array => $category ? [
                    'data-type' => $category->getType()->value,
                    'data-color' => $category->getColor(),
                ] : [],
                'attr' => [
                    'data-role' => 'transaction-category',
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Importo',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => '0.01',
                    'step' => '0.01',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Descrizione',
                'attr' => [
                    'placeholder' => 'Descrivi il movimento',
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Data',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'categories' => [],
        ]);

        $resolver->setAllowedTypes('categories', 'array');
    }
}
