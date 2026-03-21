<?php

namespace Orchestra\DuskUpdaterApi;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

use function Orchestra\Sidekick\join_paths;

class ChromeVersionFinder
{
    /**
     * The legacy versions for the ChromeDriver.
     *
     * @var array<int, string>
     */
    protected array $legacyVersions = [
        43 => '2.20',
        44 => '2.20',
        45 => '2.20',
        46 => '2.21',
        47 => '2.21',
        48 => '2.21',
        49 => '2.22',
        50 => '2.22',
        51 => '2.23',
        52 => '2.24',
        53 => '2.26',
        54 => '2.27',
        55 => '2.28',
        56 => '2.29',
        57 => '2.29',
        58 => '2.31',
        59 => '2.32',
        60 => '2.33',
        61 => '2.34',
        62 => '2.35',
        63 => '2.36',
        64 => '2.37',
        65 => '2.38',
        66 => '2.40',
        67 => '2.41',
        68 => '2.42',
        69 => '2.44',
    ];

    /**
     * Find selected ChromeDriver version URL.
     *
     * @throws \Exception
     */
    public function findVersionUrl(?string $version): string
    {
        if (! $version) {
            return $this->latestVersion();
        }

        if (! ctype_digit((string) $version)) {
            return $version;
        }

        $version = (int) $version;

        if ($version < 70) {
            return $this->legacyVersions[$version];
        } elseif ($version < 115) {
            return $this->fetchChromeVersionFromUrl($version);
        }

        $milestones = $this->resolveChromeVersionsPerMilestone();

        return $milestones['milestones'][$version]['version']
            ?? throw new Exception('Could not determine the ChromeDriver version.');
    }

    /**
     * Get the latest stable ChromeDriver version.
     *
     * @throws \Exception
     */
    public function latestVersion(): string
    {
        $versions = json_decode(
            HttpClient::fetch('https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json'), true
        );

        return $versions['channels']['Stable']['version']
            ?? throw new Exception('Could not get the latest ChromeDriver version.');
    }

    /**
     * Detect the installed Chrome/Chromium version.
     *
     * @return array{full: string, semver: string, major: int, minor: int, patch: int}
     *
     * @throws \InvalidArgumentException
     *
     * @codeCoverageIgnore
     */
    public function installedChromeVersion(string $operatingSystem, ?string $directory = null): array
    {
        if ($directory) {
            if ($operatingSystem === 'win') {
                throw new InvalidArgumentException('Chrome version cannot be detected in custom installation path on Windows.');
            }

            $commands = [\sprintf('%s --version', escapeshellcmd($directory))];
        } else {
            $commands = OperatingSystem::chromeVersionCommands($operatingSystem);
        }

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command);

            $process->run();

            if ($process->getExitCode() != 0) {
                continue;
            }

            if (preg_match('/(\d+)\.(\d+)\.(\d+)(\.\d+)?/', $process->getOutput(), $matches) === false) {
                continue;
            }

            /** @var array{0: non-falsy-string, 1: numeric-string, 2: numeric-string, 3: numeric-string} $matches */
            $semver = implode('.', [$matches[1], $matches[2], $matches[3]]);

            return [
                'full' => $matches[0],
                'semver' => $semver,
                'major' => (int) $matches[1],
                'minor' => (int) $matches[2],
                'patch' => (int) $matches[3],
            ];
        }

        throw new InvalidArgumentException(
            'Chrome version could not be detected. Please submit an issue: https://github.com/orchestral/dusk-updater-api'
        );
    }

    /**
     * Detect the installed ChromeDriver version.
     *
     * @return array{full: string|null, semver: string|null, major: int|null, minor: int|null, patch: int|null}
     *
     * @throws \InvalidArgumentException
     *
     * @codeCoverageIgnore
     */
    public function installedChromeDriverVersion(string $operatingSystem, string $directory): array
    {
        $filename = OperatingSystem::chromeDriverBinary($operatingSystem);

        if (! is_file(join_paths($directory, $filename))) {
            return [
                'full' => null,
                'semver' => null,
                'major' => null,
                'minor' => null,
                'patch' => null,
            ];
        }

        $command = \sprintf('%s --version', escapeshellcmd(join_paths(rtrim($directory, DIRECTORY_SEPARATOR), $filename)));
        $process = Process::fromShellCommandline($command);

        $process->run();

        if ($process->getExitCode() == 0) {
            if (preg_match('/ChromeDriver\s(\d+)\.(\d+)\.(\d+)(\.\d+)?\s[\w\D]+/', $process->getOutput(), $matches) !== false) {
                $semver = implode('.', [$matches[1], $matches[2], $matches[3]]);

                return [
                    'full' => $semver,
                    'semver' => $semver,
                    'major' => (int) $matches[1],
                    'minor' => (int) $matches[2],
                    'patch' => (int) $matches[3],
                ];
            }
        }

        throw new InvalidArgumentException(
            'ChromeDriver version could not be detected. Please submit an issue: https://github.com/orchestral/dusk-updater-api'
        );
    }

    /**
     * Get the chrome version from URL.
     */
    public function fetchChromeVersionFromUrl(int $version): string
    {
        return trim((string) HttpClient::fetch(
            \sprintf('https://chromedriver.storage.googleapis.com/LATEST_RELEASE_%d', $version)
        ));
    }

    /**
     * Get the chrome versions per milestone.
     */
    public function resolveChromeVersionsPerMilestone(): array
    {
        return json_decode(
            HttpClient::fetch('https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json'), true
        );
    }

    /**
     * Resolve the download url.
     *
     * @throws \Exception
     */
    public function resolveChromeDriverDownloadUrl(string $version, string $operatingSystem): string
    {
        $slug = OperatingSystem::chromeDriverSlug($operatingSystem, $version);

        if (version_compare($version, '115.0', '<')) {
            return \sprintf('https://chromedriver.storage.googleapis.com/%s/chromedriver_%s.zip', $version, $slug);
        }

        $milestone = (int) $version;

        $versions = $this->resolveChromeVersionsPerMilestone();

        /** @var array<string, mixed> $chromedrivers */
        $chromedrivers = $versions['milestones'][$milestone]['downloads']['chromedriver']
            ?? throw new Exception('Could not get the ChromeDriver version.');

        foreach ($chromedrivers as $chromedriver) {
            if ($chromedriver['platform'] === $slug) {
                return $chromedriver['url'];
            }
        }

        throw new Exception('Could not get the ChromeDriver version.');
    }
}
