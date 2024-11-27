<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit']; // Получаем флаг "редактирование" из настроек

        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Enter the content here...'
                ]
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => !$isEdit, // Всегда указываем, что поле необязательно
                'attr' => ['accept' => 'image/*'],
                'constraints' => [ // Убираем ограничения для редактирования
                    new File([
                        'maxSize' => '15m',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file.',
                    ]),
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title',
                'multiple' => true,  // Позволяет выбрать несколько категорий
                'expanded' => true,  // Отображает как чекбоксы (можно убрать, чтобы использовать селект)
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Указываем, что по умолчанию форма не является формой редактирования
            'is_edit' => false,
        ]);
    }
}