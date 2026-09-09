<?php
declare(strict_types = 1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_key_exists;

/**
 *
 */
#[
    AsCommand(
        name: 'dotenv:show',
        description: 'Lists all environment variables loaded by Dotenv in the format VAR=VALUE.',
    ),
]
class DotenvShowCommand extends Command {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(private TranslatorInterface $translator) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void {
        $this->addArgument(
            'filter',
            InputArgument::OPTIONAL,
            $this->translator->trans('Filter for the name of the variables'),
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if (!array_key_exists('SYMFONY_DOTENV_VARS', $_SERVER)) {
            $io->error(
                $this->translator->trans(
                    'Dotenv not initialized (missing SYMFONY_DOTENV_VARS).',
                ),
            );

            return Command::FAILURE;
        }

        $filter = $input->getArgument('filter');
        $dotenvVars = explode(',', $_SERVER['SYMFONY_DOTENV_VARS']);

        if (empty($dotenvVars)) {
            $io->warning(
                $this->translator->trans('No environment variables found.'),
            );

            return Command::SUCCESS;
        }

        $matchedVars = [];
        foreach ($dotenvVars as $var) {
            if ($filter && stripos($var, $filter) === false) {
                continue;
            }

            $value = $_SERVER[$var] ?? '';
            $matchedVars[] = $var.'='.$value;
        }

        if (empty($matchedVars)) {
            $io->warning(
                $this->translator->trans(
                    'No environment variables found matching filter: {filter}',
                    ['filter' => $filter],
                ),
            );

            return Command::SUCCESS;
        }

        $io->title($this->translator->trans('Environment Variables'));

        if ($filter) {
            $io->note(
                $this->translator->trans('Filtered by: {filter}', [
                    'filter' => $filter,
                ]),
            );
        }

        foreach ($matchedVars as $varLine) {
            $output->writeln($varLine);
        }

        $io->success(
            $this->translator->trans('Found {count} environment variables', [
                'count' => count($matchedVars),
            ]),
        );

        return Command::SUCCESS;
    }
}
