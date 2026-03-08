<?php

declare(strict_types=1);

namespace Fissible\Drift\Console;

use Fissible\Accord\FileSpecSource;
use Fissible\Accord\SpecSourceInterface;
use Fissible\Drift\ChangelogGenerator;
use Fissible\Drift\DriftDetector;
use Fissible\Drift\RouteInspectorInterface;
use Fissible\Drift\VersionAnalyser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'accord:version', description: 'Analyse drift and write an updated OpenAPI spec with semver recommendation')]
class VersionCommand extends Command
{
    public function __construct(
        private readonly RouteInspectorInterface $inspector,
        private readonly DriftDetector $detector,
        private readonly VersionAnalyser $analyser,
        private readonly ChangelogGenerator $changelog,
        private readonly SpecSourceInterface $specSource,
        private readonly string $basePath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('api-version', null, InputOption::VALUE_REQUIRED, 'URI version to analyse (e.g. v1)', 'v1')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without writing any files')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getOption('api-version');
        $dryRun  = (bool) $input->getOption('dry-run');
        $yes     = (bool) $input->getOption('yes');

        $routes = array_values(array_filter(
            $this->inspector->getRoutes(),
            fn($r) => preg_match('/\/' . preg_quote($version, '/') . '(?:\/|$)/', $r->path),
        ));

        $report         = $this->detector->detect($routes, $version);
        $recommendation = $this->analyser->analyse($report);

        $output->writeln('');
        $output->writeln("<info>Drift analysis for {$version}</info>");
        $output->writeln('  ' . $report->summary());
        $output->writeln('  Recommendation: <comment>' . $recommendation->label() . '</comment>');
        $output->writeln('  Reason: ' . $recommendation->reason);

        if (!$recommendation->hasChange()) {
            $output->writeln('<info>Spec is up to date. Nothing to do.</info>');
            return Command::SUCCESS;
        }

        if ($recommendation->requiresNewUriVersion) {
            $output->writeln('');
            $output->writeln('<error>Breaking changes detected. A new URI version is required.</error>');
            $output->writeln('  A new spec file will be created for the next major version.');
        }

        if ($dryRun) {
            $output->writeln('');
            $output->writeln('<comment>Dry run — no files written.</comment>');
            return Command::SUCCESS;
        }

        if (!$yes && !$this->confirm($input, $output, 'Proceed? [y/N] ')) {
            $output->writeln('Aborted.');
            return Command::SUCCESS;
        }

        $this->writeUpdatedSpec($recommendation->recommendedVersion, $version, $recommendation, $output);

        $changelogPath = $this->basePath . '/CHANGELOG.md';
        $entry         = $this->changelog->generate($report, $recommendation);
        $this->changelog->prepend($entry, $changelogPath);
        $output->writeln("  Changelog updated: {$changelogPath}");

        return Command::SUCCESS;
    }

    private function writeUpdatedSpec(
        string $newSemver,
        string $uriVersion,
        \Fissible\Drift\VersionRecommendation $recommendation,
        OutputInterface $output,
    ): void {
        $spec = $this->specSource->load($uriVersion);

        if ($spec === null) {
            $output->writeln('<error>No existing spec found for ' . $uriVersion . '. Run accord:generate first.</error>');
            return;
        }

        $data = $spec->getSerializableData();
        $data->info->version = $newSemver;

        $targetVersion = $recommendation->requiresNewUriVersion
            ? 'v' . ((int) ltrim($uriVersion, 'v') + 1)
            : $uriVersion;

        // Resolve output path — default to resources/openapi/{version}.yaml
        $specPath = $this->basePath . '/resources/openapi/' . $targetVersion . '.yaml';

        if ($recommendation->requiresNewUriVersion && $this->specSource instanceof FileSpecSource) {
            $existing = $this->specSource->resolvedPath($uriVersion);
            $specPath = $existing
                ? dirname($existing) . '/' . $targetVersion . '.yaml'
                : $specPath;
        }

        @mkdir(dirname($specPath), recursive: true);
        file_put_contents($specPath, Yaml::dump(json_decode(json_encode($data), true), 10, 2));

        $output->writeln("  Spec written: {$specPath}");
    }

    private function confirm(InputInterface $input, OutputInterface $output, string $question): bool
    {
        $output->write($question);
        $answer = trim((string) fgets(STDIN));
        return strtolower($answer) === 'y';
    }
}
