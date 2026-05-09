<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-database-csv',
    description: 'Exporte chaque table SQL vers un fichier CSV (UTF-8). Attention : données sensibles (mots de passe hashés, emails, etc.).',
)]
final class ExportDatabaseCsvCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'output-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Répertoire de sortie',
            'var/export/csv'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = $input->getOption('output-dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        sort($tables);

        foreach ($tables as $table) {
            $quotedTable = $this->connection->quoteSingleIdentifier($table);
            $sql = sprintf('SELECT * FROM %s', $quotedTable);
            $rows = $this->connection->fetchAllAssociative($sql);

            $columns = $schemaManager->listTableColumns($table);
            $columnNames = array_map(static fn ($col) => $col->getName(), $columns);

            $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $table) ?: 'table';
            $filename = rtrim($dir, '/').'/'.$safeBase.'.csv';
            $fp = fopen($filename, 'w');
            if ($fp === false) {
                $io->error('Impossible d\'écrire : '.$filename);

                return Command::FAILURE;
            }

            if ($rows === []) {
                fputcsv($fp, $columnNames);
            } else {
                fputcsv($fp, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($fp, array_map($this->normalizeCell(...), $row));
                }
            }
            fclose($fp);

            $io->writeln(sprintf('%s — %d ligne(s)', $filename, count($rows)));
        }

        $io->success(sprintf('Export terminé : %d fichier(s) dans %s', count($tables), $dir));

        return Command::SUCCESS;
    }

    private function normalizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
