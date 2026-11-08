<?php

declare(strict_types = 1);

namespace App\Infrastructure\Console;

use App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest;
use App\Application\Admin\UseCase\User\Role\ChangeUserRoleUseCase;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\ValidationException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dá um papel a uma conta pelo console:
 *
 *     php bin/console app:user:role ana@example.com owner
 *
 * É como se cria o primeiro ROLE_OWNER: pela API só um owner dá esse
 * papel. Usa o mesmo ChangeUserRoleUseCase da API, sem a regra de quem
 * administra quem (quem roda o comando tem acesso ao servidor) e com a do
 * último owner; a mudança entra no log de auditoria com actor null.
 *
 * O papel aceita o nome com ou sem o prefixo, em maiúsculas ou não
 * (owner, ROLE_OWNER).
 *
 * NOTA (nov/2026): criado com a hierarquia de papéis.
 */
#[
    AsCommand(
        name: 'app:user:role',
        description: 'Gives a role to an account: ROLE_USER, ROLE_DEV, ROLE_ADMIN or ROLE_OWNER (the way to create the first owner).',
    ),
]
final class ChangeUserRoleCommand extends Command {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                 $userRepository
     * @param \App\Application\Admin\UseCase\User\Role\ChangeUserRoleUseCase $changeUserRoleUseCase
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ChangeUserRoleUseCase $changeUserRoleUseCase,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the account')
            ->addArgument('role', InputArgument::REQUIRED, 'The new role: user, dev, admin or owner (with or without the ROLE_ prefix)');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) $input->getArgument('email'));
        $role = strtoupper(trim((string) $input->getArgument('role')));

        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            $io->error(sprintf('There is no account with the email "%s".', $email));

            return Command::FAILURE;
        }

        try {
            $response = $this->changeUserRoleUseCase->execute(
                new ChangeUserRoleRequest(
                    userId: (int) $user->getId(),
                    actorId: null,
                    role: $role,
                ),
            );
        } catch (ValidationException $exception) {
            $io->error($exception->getErrors());

            return Command::FAILURE;
        } catch (DomainException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('%s: %s -> %s', $email, $response->previousRole, $response->role));

        if (!$user->isVerified()) {
            $io->warning('This account has not confirmed its email yet: the login is refused until it does (or until an admin confirms it).');
        }

        return Command::SUCCESS;
    }
}
