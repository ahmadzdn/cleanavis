<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:export-database-sql',
    description: 'Exporte la base vers un fichier .sql (mysqldump/pg_dump/sqlite3 si dispo, sinon INSERT générés).',
)]
final class ExportDatabaseSqlCommand extends Command
{
    /** Ordre respectant les FK CleanAvis (email_log → customer_order / contact_message). */
    private const TABLE_INSERT_ORDER = [
        'doctrine_migration_versions',
        'messenger_messages',
        'user',
        'pack_offer',
        'faq_item',
        'contact_message',
        'customer_order',
        'email_log',
        'site_visit',
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Fichier .sql de sortie',
            'exports/database_complet.sql'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getOption('output');
        $parent = \dirname($path);
        if (!is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        if ($this->tryNativeDump((string) $path, $io)) {
            return Command::SUCCESS;
        }

        $io->note('Outil natif (mysqldump / pg_dump / sqlite3) indisponible ou échec — génération SQL avec INSERT.');

        return $this->exportInsertSql((string) $path, $io);
    }

    private function tryNativeDump(string $path, SymfonyStyle $io): bool
    {
        $params = $this->connection->getParams();
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof SQLitePlatform) {
            $dbPath = $this->resolveSqlitePath($params);
            if ($dbPath === null || !is_readable($dbPath)) {
                return false;
            }
            $process = new Process(['sqlite3', $dbPath, '.dump']);
            $process->setTimeout(600);
            $process->run();
            if (!$process->isSuccessful()) {
                $io->warning($process->getErrorOutput() ?: $process->getOutput());

                return false;
            }
            file_put_contents($path, "-- SQLite dump (sqlite3 .dump)\n".$process->getOutput());

            $io->success(sprintf('Export SQLite natif : %s', $path));

            return true;
        }

        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? null;
        $user = $params['user'] ?? '';
        $password = $params['password'] ?? '';
        $dbname = $params['dbname'] ?? $params['path'] ?? '';

        if ($platform instanceof MySQLPlatform && $dbname !== '') {
            $cmd = ['mysqldump', '--single-transaction', '--routines', '--triggers', '--add-drop-table', '-h', $host];
            if ($port !== null) {
                $cmd[] = '-P';
                $cmd[] = (string) $port;
            }
            $cmd[] = '-u';
            $cmd[] = $user;
            $cmd[] = '-p'.$password;
            $cmd[] = $dbname;
            $process = new Process($cmd);
            $process->setTimeout(600);
            $process->run();
            if (!$process->isSuccessful()) {
                return false;
            }
            file_put_contents($path, "-- MySQL dump (mysqldump)\n".$process->getOutput());
            $io->success(sprintf('Export MySQL natif : %s', $path));

            return true;
        }

        if ($platform instanceof PostgreSQLPlatform && $dbname !== '') {
            $env = array_merge($_ENV, ['PGPASSWORD' => $password]);
            $portPg = $port ?? 5432;
            $cmd = [
                'pg_dump',
                '-h', $host,
                '-p', (string) $portPg,
                '-U', $user,
                '-d', $dbname,
                '--clean',
                '--if-exists',
            ];
            $process = new Process($cmd, null, $env);
            $process->setTimeout(600);
            $process->run();
            if (!$process->isSuccessful()) {
                return false;
            }
            file_put_contents($path, "-- PostgreSQL dump (pg_dump)\n".$process->getOutput());
            $io->success(sprintf('Export PostgreSQL natif : %s', $path));

            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveSqlitePath(array $params): ?string
    {
        $path = isset($params['path']) ? (string) $params['path'] : '';
        if ($path === '') {
            return null;
        }
        $path = urldecode($path);
        if ($path[0] !== '/') {
            return $this->projectDir.'/'.ltrim($path, '/');
        }

        return $path;
    }

    private function exportInsertSql(string $path, SymfonyStyle $io): int
    {
        $platform = $this->connection->getDatabasePlatform();
        $schemaManager = $this->connection->createSchemaManager();
        $allTables = $schemaManager->listTableNames();

        $ordered = [];
        foreach (self::TABLE_INSERT_ORDER as $t) {
            if (\in_array($t, $allTables, true)) {
                $ordered[] = $t;
            }
        }
        foreach ($allTables as $t) {
            if (!\in_array($t, $ordered, true)) {
                $ordered[] = $t;
            }
        }

        $fp = fopen($path, 'w');
        if ($fp === false) {
            $io->error('Impossible d\'écrire : '.$path);

            return Command::FAILURE;
        }

        fwrite($fp, "-- Export CleanAvis (INSERT générés) — ".date('c')."\n");
        fwrite($fp, "-- Restaurer sur une base vide avec le même schéma (doctrine:schema:create ou migrations).\n\n");

        if ($platform instanceof MySQLPlatform) {
            fwrite($fp, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\n\n");
        }

        if ($platform instanceof PostgreSQLPlatform) {
            fwrite($fp, "SET session_replication_role = replica;\n\n");
        }

        foreach ($ordered as $table) {
            $quotedTable = $this->connection->quoteSingleIdentifier($table);
            $sql = sprintf('SELECT * FROM %s', $quotedTable);
            $rows = $this->connection->fetchAllAssociative($sql);

            fwrite($fp, '-- '.$table."\n");
            if ($rows === []) {
                fwrite($fp, "-- (vide)\n\n");

                continue;
            }

            $columns = array_keys($rows[0]);
            $colList = implode(', ', array_map(fn (string $c) => $this->connection->quoteSingleIdentifier($c), $columns));

            foreach ($rows as $row) {
                $vals = [];
                foreach ($columns as $col) {
                    $vals[] = $this->quoteSqlValue($row[$col] ?? null);
                }
                fwrite($fp, 'INSERT INTO '.$quotedTable.' ('.$colList.') VALUES ('.implode(', ', $vals).");\n");
            }
            fwrite($fp, "\n");
        }

        if ($platform instanceof MySQLPlatform) {
            fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n");
        }

        if ($platform instanceof PostgreSQLPlatform) {
            fwrite($fp, "SET session_replication_role = DEFAULT;\n");
        }

        fclose($fp);

        $io->success(sprintf('Export SQL écrit : %s', $path));

        return Command::SUCCESS;
    }

    private function quoteSqlValue(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if ($v instanceof \DateTimeInterface) {
            return $this->connection->quote($v->format('Y-m-d H:i:s'));
        }
        if (\is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (\is_int($v) || \is_float($v)) {
            return (string) $v;
        }

        return $this->connection->quote((string) $v);
    }
}
