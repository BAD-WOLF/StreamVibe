<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dotenv:show',
    description: 'Lista todas as variáveis de ambiente carregadas pelo Dotenv no formato VAR=VALOR.',
)]
class DotenvShowCommand extends Command
{
    /**
     * @param string $projectDirectory
     */
    public function __construct() {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filter', InputArgument::OPTIONAL, 'Filtro para o nome das variáveis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!\array_key_exists('SYMFONY_DOTENV_VARS', $_SERVER)) {
            $io->error('Dotenv não foi inicializado (faltando SYMFONY_DOTENV_VARS).');
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
