<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class TextInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'label' => 'Wpisz tekst',
            'attr' => ['rows' => 5],
            'constraints' => [new NotBlank(message: 'Wpisz tekst przed wysłaniem.')],
        ]);
    }
}
