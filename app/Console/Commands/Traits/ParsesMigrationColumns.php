<?php

namespace App\Console\Commands\Traits;

use Illuminate\Support\Facades\File;

trait ParsesMigrationColumns
{
    protected function getSortedMigrationFiles(): array
    {
        $path = database_path('migrations');
        $files = File::glob($path.'/*.php');
        $files = array_filter($files, function ($file) {
            return preg_match('/\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', basename($file));
        });
        sort($files);

        return $files;
    }

    protected function buildColumnOrders(array $migrationFiles): array
    {
        $tables = [];

        foreach ($migrationFiles as $file) {
            $content = File::get($file);
            $upMethod = $this->extractUpMethod($content) ?? $content;

            if (preg_match_all(
                '/Schema::create\(\s*\'([^\']+)\'\s*,\s*function\s*\([^)]*\)\s*\{(.*?)\}\s*\)\s*;/s',
                $upMethod,
                $createMatches,
                PREG_SET_ORDER
            )) {
                foreach ($createMatches as $match) {
                    $tableName = $match[1];
                    $body = $match[2];
                    if (! isset($tables[$tableName])) {
                        $tables[$tableName] = $this->extractCreateColumns($body);
                    }
                }
            }

            if (preg_match_all(
                '/Schema::table\(\s*\'([^\']+)\'\s*,\s*function\s*\([^)]*\)\s*\{(.*?)\}\s*\)\s*;/s',
                $upMethod,
                $alterMatches,
                PREG_SET_ORDER
            )) {
                foreach ($alterMatches as $match) {
                    $tableName = $match[1];
                    $body = $match[2];
                    if (! isset($tables[$tableName])) {
                        $tables[$tableName] = [];
                    }
                    $this->applyAlterChanges($tables[$tableName], $body);
                }
            }
        }

        ksort($tables);

        return $tables;
    }

    private function extractUpMethod(string $content): ?string
    {
        if (preg_match('/public\s+function\s+up\s*\(\s*\)\s*:\s*void\s*\{(.*?)\}\s*public\s+function\s+down\s*\(/s', $content, $m)) {
            return $m[1];
        }
        if (preg_match('/public\s+function\s+up\s*\(\s*\)\s*\{(.*?)\}\s*public\s+function\s+down\s*\(/s', $content, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractCreateColumns(string $body): array
    {
        $columns = [];

        if (preg_match_all('/\$table->(?!timestamps|softDeletes|dropSoftDeletes|dropColumn|dropIndex|dropForeign|dropPrimary|dropUnique|index|unique|foreign|primary|renameColumn|morphs|nullableMorphs|rememberToken)\w+\(\s*\'(\w+)\'\s*/', $body, $matches)) {
            $columns = $matches[1];
        }

        if (preg_match('/\$table->rememberToken\s*\(\s*\)/', $body)) {
            $columns[] = 'remember_token';
        }
        if (preg_match('/\$table->timestamps\s*\(\s*\)/', $body)) {
            $columns[] = 'created_at';
            $columns[] = 'updated_at';
        }
        if (preg_match('/\$table->softDeletes\s*\(\s*\)/', $body)) {
            $columns[] = 'deleted_at';
        }

        return $columns;
    }

    private function applyAlterChanges(array &$columns, string $body): void
    {
        if (preg_match_all('/\$table->dropColumn\s*\(\s*\'(\w+)\'\s*\)/', $body, $dropMatches)) {
            foreach ($dropMatches[1] as $col) {
                $columns = array_values(array_filter($columns, fn ($c) => $c !== $col));
            }
        }

        if (preg_match_all('/\$table->dropColumn\s*\(\s*\[(.*?)\]\s*\)/', $body, $dropArrayMatches)) {
            foreach ($dropArrayMatches[1] as $listStr) {
                $dropCols = explode(',', $listStr);
                foreach ($dropCols as $dc) {
                    $col = trim($dc, " \t\n\r\0\x0B'\"");
                    $columns = array_values(array_filter($columns, fn ($c) => $c !== $col));
                }
            }
        }

        if (preg_match_all('/\$table->renameColumn\s*\(\s*\'(\w+)\'\s*,\s*\'(\w+)\'\s*\)/', $body, $renameMatches, PREG_SET_ORDER)) {
            foreach ($renameMatches as $m) {
                $pos = array_search($m[1], $columns, true);
                if ($pos !== false) {
                    $columns[$pos] = $m[2];
                }
            }
        }

        if (preg_match_all(
            '/\$table->(?:(?!timestamps|softDeletes|dropSoftDeletes|dropColumn|dropIndex|dropForeign|dropPrimary|dropUnique|index|unique|foreign|primary|renameColumn|morphs|nullableMorphs|rememberToken)\w+)\(\s*\'(\w+)\'\s*[^;]*?->after\(\s*\'(\w+)\'\s*\)/s',
            $body,
            $addMatches,
            PREG_SET_ORDER
        )) {
            foreach ($addMatches as $m) {
                $colName = $m[1];
                $afterCol = $m[2];
                if (! in_array($colName, $columns, true)) {
                    $pos = array_search($afterCol, $columns, true);
                    if ($pos !== false) {
                        array_splice($columns, $pos + 1, 0, [$colName]);
                    } else {
                        $columns[] = $colName;
                    }
                }
            }
        }

        if (preg_match_all(
            '/\$table->(?:(?!timestamps|softDeletes|dropSoftDeletes|dropColumn|dropIndex|dropForeign|dropPrimary|dropUnique|index|unique|foreign|primary|renameColumn|morphs|nullableMorphs|rememberToken)\w+)\(\s*\'(\w+)\'\s*/s',
            $body,
            $addPlainMatches
        )) {
            foreach ($addPlainMatches[1] as $colName) {
                $hasAfter = preg_match('/\$table->\w+\(\s*'.preg_quote($colName, '/').'\'[^;]*?->after\(/s', $body);
                if (! $hasAfter && ! in_array($colName, $columns, true)) {
                    $columns[] = $colName;
                }
            }
        }

        if (preg_match('/\$table->timestamps\s*\(\s*\)/', $body)) {
            if (! in_array('created_at', $columns, true)) {
                $columns[] = 'created_at';
            }
            if (! in_array('updated_at', $columns, true)) {
                $columns[] = 'updated_at';
            }
        }

        if (preg_match('/\$table->softDeletes\s*\(\s*\)/', $body)) {
            if (! in_array('deleted_at', $columns, true)) {
                $columns[] = 'deleted_at';
            }
        }

        if (preg_match('/\$table->rememberToken\s*\(\s*\)/', $body)) {
            if (! in_array('remember_token', $columns, true)) {
                $columns[] = 'remember_token';
            }
        }

        if (preg_match('/\$table->dropSoftDeletes\s*\(\s*\)/', $body)) {
            $columns = array_values(array_filter($columns, fn ($c) => $c !== 'deleted_at'));
        }
    }
}
