<?php

declare(strict_types=1);

namespace Fissible\Drift\Console;

use Fissible\Drift\CoverageAnalyser;
use Fissible\Drift\CoverageStatus;
use Fissible\Drift\RouteInspectorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'drift:coverage', description: 'Check that all registered routes have controller implementations')]
class CoverageCommand extends Command
{
    public function __construct(
        private readonly RouteInspectorInterface $inspector,
        private readonly CoverageAnalyser $analyser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Limit check to a specific API version (e.g. v1).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $this->inspector->getRoutes();
        $version = $input->getOption('api-version');

        if ($version !== null) {
            $number = ltrim($version, 'v');
            $routes = array_values(array_filter(
                $routes,
                fn ($r) => preg_match('/\/v'.$number.'(?:\/|$)/', $r->path),
            ));
        }

        if (empty($routes)) {
            $output->writeln('<comment>No routes found to check.</comment>');

            return Command::SUCCESS;
        }

        $report = $this->analyser->analyse($routes);

        $table = new Table($output);
        $table->setHeaders(['Coverage', 'Method', 'Path', 'Action']);

        foreach ($report->results as $result) {
            $label = match ($result->status) {
                CoverageStatus::Implemented => '<info>IMPLEMENTED</info>',
                CoverageStatus::Missing => '<error>MISSING</error>',
                CoverageStatus::Unknown => '<comment>UNKNOWN</comment>',
            };

            $table->addRow([
                $label,
                $result->route->method,
                $result->route->openApiPath(),
                $result->route->action ?? '(closure)',
            ]);
        }

        $table->render();
        $output->writeln('');
        $output->writeln($report->summary());

        if (! $report->isFullyCovered()) {
            $output->writeln('');
            $output->writeln('<error>Coverage check failed: '.count($report->missing()).' route(s) have no implementation.</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
