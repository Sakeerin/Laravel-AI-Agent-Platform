<?php

namespace App\Console\Commands;

use App\Services\Tools\ToolRegistry;
use Illuminate\Console\Command;

class SyncSkillsCommand extends Command
{
    protected $signature = 'skills:sync';
    protected $description = 'Sync all registered tools/skills to the database';

    public function handle(ToolRegistry $registry): int
    {
        $this->info('Syncing skills to database...');

        $registry->syncToDatabase();

        $tools = $registry->all();
        $this->table(
            ['Name', 'Category', 'Approval Required'],
            array_map(fn($t) => [$t->name(), $t->category(), $t->requiresApproval() ? 'Yes' : 'No'], $tools)
        );

        $this->info(count($tools) . ' skills synced successfully.');

        return self::SUCCESS;
    }
}
