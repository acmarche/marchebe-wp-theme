<?php

namespace AcMarche\Theme\Command;

use AcMarche\Theme\Lib\Pivot\Enums\ContentEnum;
use AcMarche\Theme\Lib\Pivot\Repository\PivotApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'pivot:query',
    description: ' ',
)]
class PivotQueryCommand extends Command
{
    private SymfonyStyle $io;
    private bool $purge = false;

    protected function configure(): void
    {
        $this->setDescription('fetch pivot data');
        $this->addOption('parse', "parse", InputOption::VALUE_NONE, 'Parse data');
        $this->addOption('codeCgt', "codeCgt", InputOption::VALUE_REQUIRED, 'Dump one event');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $parse = (bool)$input->getOption('parse');
        $codeCgt = (string)$input->getOption('codeCgt');

        if ($codeCgt) {
            $pivotApi = new PivotApi();

            try {
                $response = $pivotApi->loadEvent($codeCgt, ContentEnum::LVL4->value);

            } catch (\Exception|TransportExceptionInterface$e) {
                $this->io->error($e->getMessage());

                return Command::FAILURE;
            }

            try {
                $jsonString = $response?->getContent();
            } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|RedirectionExceptionInterface $e) {

                $this->io->error($e->getMessage());

                return Command::FAILURE;
            }

            $this->io->writeln($jsonString);

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

}