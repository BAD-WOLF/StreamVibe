<?php

declare(strict_types = 1);

namespace App\Infrastructure\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class ReverifyFormType extends AbstractType {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(private TranslatorInterface $translator) {
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $options
     *
     * @return void
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('resend email verify '),
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => $this->translator->trans(
                        'Type your email ...',
                    ),
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans(
                            'You have to type your email',
                        ),
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('Send Verification Email'),
            ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
