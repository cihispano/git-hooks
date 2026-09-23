<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit;

use CiHispano\Util\FilePermissions;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Exercises the distributed shell hooks exactly as a consumer would: a real Git
 * repository in a temp directory, the packaged `pre-commit` hook installed into
 * `.git/hooks`, and staged PHP files whose names contain spaces and shell
 * metacharacters. Verifies that filenames reach the QA tools as single intact
 * arguments (SEC-002) and that the hook no longer relies on `$0` interpolation
 * or `echo -e` (SEC-003/SEC-004).
 *
 * @internal
 */
final class HooksSmokeTest extends TestCase
{
    private ?string $smokeRoot = null;

    protected function setUp(): void
    {
        if (! \function_exists('proc_open')) {
            $this->markTestSkipped('proc_open() is required to run the hook smoke test.');
        }

        $this->smokeRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-hooks-smoke-' . \bin2hex(\random_bytes(6));

        $this->assertTrue(\mkdir($this->smokeRoot, FilePermissions::DIR_DEFAULT, true));
    }

    protected function tearDown(): void
    {
        if ($this->smokeRoot !== null) {
            $this->removeDirectory($this->smokeRoot);
            $this->smokeRoot = null;
        }
    }

    public function testPreCommitReceivesSpacedAndMetaFilenameAsSingleArguments(): void
    {
        $projectRoot = $this->smokeRoot;
        $this->assertNotNull($projectRoot);

        $this->initRepository($projectRoot);

        $this->assertTrue(
            \mkdir(
                $projectRoot . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'bin',
                FilePermissions::DIR_DEFAULT,
                true,
            ),
        );

        $logPath = $projectRoot . \DIRECTORY_SEPARATOR . 'tools.log';
        \file_put_contents($logPath, '');

        $this->installFakeTools($projectRoot, $logPath);

        $spacedFile = 'src' . \DIRECTORY_SEPARATOR . 'Controller With Spaces.php';
        $metaFile   = 'src' . \DIRECTORY_SEPARATOR . 'star?name.php';

        $this->writePhpSource($projectRoot, $spacedFile);
        $this->writePhpSource($projectRoot, $metaFile);

        $this->installHook($projectRoot, 'pre-commit');

        $result = $this->shellRun(
            $projectRoot,
            'git add src && git commit -q -m "feat: smoke spaced filename"',
            $logPath,
        );

        $this->assertSame(0, $result['exit'], 'Hook output: ' . $result['output']);
        $this->assertStringContainsString('All checks passed', $result['output']);

        $contents = \file_get_contents($logPath);
        $this->assertNotFalse($contents);
        $this->assertNotSame('', $contents);
        $toolCalls = \array_filter(\array_map(
            static fn (string $line): array => (array) \json_decode($line, true, 512, \JSON_THROW_ON_ERROR),
            \explode(PHP_EOL, \trim($contents)),
        ));

        $invokedTokens = [];

        foreach ($toolCalls as $arguments) {
            foreach ($arguments as $argument) {
                if (\is_string($argument)) {
                    $invokedTokens[$argument] = true;
                }
            }
        }

        foreach ([$spacedFile, $metaFile] as $filename) {
            $this->assertArrayHasKey(
                $filename,
                $invokedTokens,
                \implode(
                    ', ',
                    \array_keys($invokedTokens),
                ),
            );
        }
    }

    public function testCommitMsgHookBlocksNonConventionalSubject(): void
    {
        $projectRoot = $this->smokeRoot;
        $this->assertNotNull($projectRoot);

        $this->initRepository($projectRoot);
        $this->installHook($projectRoot, 'commit-msg');

        $result = $this->shellRun(
            $projectRoot,
            'git commit -q --allow-empty -m "subject that is too long for nothing"',
        );

        $this->assertSame(1, $result['exit'], 'Hook output: ' . $result['output']);
        $this->assertStringContainsString('Invalid format', $result['output']);
    }

    public function testCommitMsgHookPassesConventionalSubject(): void
    {
        $projectRoot = $this->smokeRoot;
        $this->assertNotNull($projectRoot);

        $this->initRepository($projectRoot);
        $this->installHook($projectRoot, 'commit-msg');

        $result = $this->shellRun($projectRoot, 'git commit -q --allow-empty -m "feat(core): add empty commit"');

        $this->assertSame(0, $result['exit'], 'Hook output: ' . $result['output']);
        $this->assertStringContainsString('Commit message format is valid', $result['output']);
    }

    public function testPrePushRunIntegrityChecks(): void
    {
        $projectRoot = $this->smokeRoot;
        $this->assertNotNull($projectRoot);

        $this->initRepository($projectRoot);
        $this->installHook($projectRoot, 'pre-push');

        $logPath = $projectRoot . \DIRECTORY_SEPARATOR . 'tools.log';
        \file_put_contents($logPath, '');

        $this->assertTrue(
            \mkdir($projectRoot . \DIRECTORY_SEPARATOR . 'vendor/bin', FilePermissions::DIR_DEFAULT, true),
        );

        foreach (['phpunit', 'phpstan'] as $tool) {
            $toolPath = $projectRoot . \DIRECTORY_SEPARATOR . 'vendor/bin/' . $tool;
            \file_put_contents($toolPath, '<?php exit(0);');
            \chmod($toolPath, FilePermissions::FILE_EXECUTABLE);
        }

        $result = $this->shellRun($projectRoot, 'printf "" | .git/hooks/pre-push origin 2>&1');

        $this->assertSame(0, $result['exit'], 'Hook output: ' . $result['output']);
        $this->assertStringContainsString('Integrity verified', $result['output']);
    }

    private function initRepository(string $projectRoot): void
    {
        $result = $this->shellRun($projectRoot, 'git init -q .');
        $this->assertSame(0, $result['exit'], $result['output']);
        $result = $this->shellRun($projectRoot, 'git config user.email hooks@cihispano.test');
        $this->assertSame(0, $result['exit'], $result['output']);
        $result = $this->shellRun($projectRoot, 'git config user.name "CiHispano Hooks"');
        $this->assertSame(0, $result['exit'], $result['output']);
        $result = $this->shellRun($projectRoot, 'git config commit.gpgsign false');
        $this->assertSame(0, $result['exit'], $result['output']);
    }

    private function installFakeTools(string $projectRoot, string $logPath): void
    {
        foreach (['php-cs-fixer', 'phpcs', 'phpstan'] as $tool) {
            $toolPath = $projectRoot . \DIRECTORY_SEPARATOR . 'vendor/bin/' . $tool;
            \file_put_contents(
                $toolPath,
                '<?php file_put_contents(getenv("CIHISPANO_HOOKS_LOG"), '
                . 'json_encode(array_slice($argv, 1)) . PHP_EOL, \FILE_APPEND);',
            );
            \chmod($toolPath, FilePermissions::FILE_EXECUTABLE);
        }
    }

    private function installHook(string $projectRoot, string $hook): void
    {
        $gitHooks = $projectRoot . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'hooks';
        if (! \is_dir($gitHooks)) {
            $this->assertTrue(\mkdir($gitHooks, FilePermissions::DIR_DEFAULT, true));
        }
        \copy($this->packageHook($hook), $gitHooks . \DIRECTORY_SEPARATOR . $hook);
        \chmod($gitHooks . \DIRECTORY_SEPARATOR . $hook, FilePermissions::FILE_EXECUTABLE);
    }

    private function packageHook(string $hook): string
    {
        return \dirname(__DIR__, 2) .
            \DIRECTORY_SEPARATOR . 'src' .
            \DIRECTORY_SEPARATOR . 'Hooks' .
            \DIRECTORY_SEPARATOR . $hook;
    }

    private function writePhpSource(string $projectRoot, string $relativePath): void
    {
        $target    = $projectRoot . \DIRECTORY_SEPARATOR . $relativePath;
        $directory = \dirname($target);
        if (! \is_dir($directory)) {
            $this->assertTrue(\mkdir($directory, FilePermissions::DIR_DEFAULT, true));
        }
        \file_put_contents($target, '<?php echo "ok";');
    }

    /**
     * @return array{exit: int, output: string}
     */
    private function shellRun(string $cwd, string $command, ?string $envLogPath = null): array
    {
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cmd = 'sh -c ' . \escapeshellarg($command);
        if ($envLogPath !== null) {
            $cmd = 'CIHISPANO_HOOKS_LOG=' . \escapeshellarg($envLogPath) . ' ' . $cmd;
        }
        $process = \proc_open(
            $cmd,
            $descriptorSpec,
            $pipes,
            $cwd,
        );
        $this->assertIsResource($process);

        $stdout = (string) \stream_get_contents($pipes[1]);
        $stderr = (string) \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);

        $exit = \proc_close($process);

        return ['exit' => $exit, 'output' => $stdout . $stderr];
    }

    private function removeDirectory(string $path): void
    {
        if (! \file_exists($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo) {
                if ($item->isDir()) {
                    \rmdir($item->getPathname());

                    continue;
                }

                \unlink($item->getPathname());
            }
        }

        \rmdir($path);
    }
}
