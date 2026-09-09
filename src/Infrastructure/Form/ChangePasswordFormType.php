<?php
declare(strict_types = 1);

namespace App\Infrastructure\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class ChangePasswordFormType extends AbstractType {
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
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'options' => [
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ],
            'first_options' => [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans(
                            'Please enter a password',
                        ),
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => $this->translator->trans(
                            'Your password should be at least {{ limit }} characters',
                        ),
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new PasswordStrength(),
                    new NotCompromisedPassword(),
                ],
                'label' => $this->translator->trans('New password'),
            ],
            'second_options' => [
                'label' => $this->translator->trans('Repeat Password'),
            ],
            'invalid_message' => $this->translator->trans(
                'The password fields must match.',
            ),
            // Instead of being set onto the object directly,
            // this is read and encoded in the controller
            'mapped' => false,
        ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([]);
    }
}
