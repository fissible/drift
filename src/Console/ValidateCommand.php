<?php

declare(strict_types=1);

namespace Fissible\Drift\Console;

use Fissible\Accord\VersionExtractor;
use Fissible\Drift\DriftDetector;
use Fissible\Drift\RouteInspectorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'accord:validate', description: 'Check if API routes are consistent with the OpenAPI spec')]
class ValidateCommand extends Command
{
    public function __construct(
        private readonly RouteInspectorInterface $inspector,
        private readonly DriftDetector $detector,
        private readonly VersionExtractor $versionExtractor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('api-version', null, InputOption::VALUE_OPTIONAL, 'Spec version to validate against (e.g. v1). Validates all detected versions if omitted.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes   = $this->inspector->getRoutes();
        $versions = $this->detectVersions($routes, $input->getOption('api-version'));

        if (empty($versions)) {
            $output->writeln('<comment>No versioned routes found. Nothing to validate.</comment>');
            return Command::SUCCESS;
        }

        $exitCode = Command::SUCCESS;

        foreach ($versions as $version) {
            $report = $this->detector->detect(
                $this->filterRoutesByVersion($routes, $version),
                $version,
            );

            $output->writeln('');
            $output->writeln("<info>Version: {$version}</info>");

            $table = new Table($output);
            $table->setHeaders(['Status', 'Method', 'Path']);

            foreach ($report->matched as $route) {
                $table->addRow(['<info>PASS</info>', $route->method, $route->path]);
            }

            foreach ($report->added as $route) {
                $table->addRow(['<comment>WARN  undocumented</comment>', $route->method, $route->path]);
                $exitCode = Command::FAILURE;
            }

            foreach ($report->removed as $route) {
                $table->addRow(['<error>FAIL  removed from app</error>', $route->method, $route->path]);
                $exitCode = Command::FAILURE;
            }

            $table->render();
            $output->writeln($report->summary());
        }

        return $exitCode;
    }

    /** @return string[] */
    private function detectVersions(array $routes, ?string $pinned): array
    {
        if ($pinned !== null) {
            return [$pinned];
        }

        $versions = [];

        foreach ($routes as $route) {
            // Extract version from the route path directly
            if (preg_match('/\/v(\d+)(?:\/|$)/', $route->path, $m)) {
                $versions['v' . $m[1]] = true;
            }
        }

        return array_keys($versions);
    }

    /** @return \Fissible\Drift\RouteDefinition[] */
    private function filterRoutesByVersion(array $routes, string $version): array
    {
        $number = ltrim($version, 'v');

        return array_values(array_filter(
            $routes,
            fn($r) => preg_match('/\/v' . $number . '(?:\/|$)/', $r->path),
        ));
    }
}
