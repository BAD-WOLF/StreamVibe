<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'dotenv:show',
    description: 'Lists all environment variables loaded by Dotenv in the format VAR=VALUE.',
)]
class DotenvShowCommand extends Command
{
    /**
     * @param string $projectDirectory
     */
    public function __construct(private TranslatorInterface $translator) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filter', InputArgument::OPTIONAL, $this->translator->trans('Filter for the name of the variables'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!\array_key_exists('SYMFONY_DOTENV_VARS', $_SERVER)) {
            $io->error($this->translator->trans('Dotenv not initialized (missing SYMFONY_DOTENV_VARS).'));
            return Command::FAILURE;
        }

        $filter = $input->getArgument('filter');
        $dotenvVars = explode(',', $_SERVER['SYMFONY_DOTENV_VARS']);

        foreach ($dotenvVars as $var) {
            if ($filter && stripos($var, $filter) === false) {
                continue;
            }

            $value = $_SERVER[$var] ?? '';
            $output->writeln($var . '=' . $value);
        }

        return Command::SUCCESS;
    }
}
