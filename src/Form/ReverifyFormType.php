<?php

declare(strict_types = 1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReverifyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options, TranslatorInterface $translator): void
    {
        $builder
            ->add('email', EmailType::class, [
                "label" => $translator->trans("resend email verify "),
                "attr" => [
                    "autocomplete" => "email",
                    "placeholder" => $translator->trans("Type your email ...")
                ],
                "constraints" => [
                    new NotBlank([
                        "message" => $translator->trans("You have to type your email")
                    ])
                ]
            ])
            ->add("submit", SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
