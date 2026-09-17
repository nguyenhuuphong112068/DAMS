<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shelves') && !Schema::hasColumn('shelves', 'max_tiers')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->unsignedInteger('max_tiers')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('tiers')) {
            Schema::table('tiers', function (Blueprint $table) {
                if (!Schema::hasColumn('tiers', 'max_locations')) {
                    $table->unsignedInteger('max_locations')->nullable()->after('name');
                }
                if (!Schema::hasColumn('tiers', 'position')) {
                    $table->unsignedInteger('position')->nullable();
                }
            });
        }

        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'position')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedInteger('position')->nullable();
            });
        }

        // Sơ đồ kho xếp tầng theo hàng và ô theo cột, nên thứ tự phải là số;
        // dữ liệu cũ chỉ có code nên suy ra bằng natural sort trong từng cấp cha.
        $this->backfillPositions('tiers', 'shelf_id');
        $this->backfillPositions('locations', 'tier_id');

        $this->backfillCapacity('tiers', 'shelf_id', 'shelves', 'max_tiers');
        $this->backfillCapacity('locations', 'tier_id', 'tiers', 'max_locations');
    }

    public function down(): void
    {
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'position')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }

        if (Schema::hasTable('tiers')) {
            Schema::table('tiers', function (Blueprint $table) {
                if (Schema::hasColumn('tiers', 'position')) {
                    $table->dropColumn('position');
                }
                if (Schema::hasColumn('tiers', 'max_locations')) {
                    $table->dropColumn('max_locations');
                }
            });
        }

        if (Schema::hasTable('shelves') && Schema::hasColumn('shelves', 'max_tiers')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->dropColumn('max_tiers');
            });
        }
    }

    private function backfillPositions(string $table, string $parentKey): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'position')) {
            return;
        }

        $rows = DB::table($table)
            ->select('id', 'code', $parentKey)
            ->whereNull('position')
            ->get();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[$row->{$parentKey} ?? 0][] = $row;
        }

        $positions = [];
        foreach ($byParent as $group) {
            usort($group, fn($a, $b) => strnatcasecmp((string) $a->code, (string) $b->code));
            foreach ($group as $index => $row) {
                $positions[(int) $row->id] = $index + 1;
            }
        }

        foreach (array_chunk($positions, 500, true) as $chunk) {
            $cases = '';
            $ids = [];
            foreach ($chunk as $id => $position) {
                $cases .= 'WHEN ' . (int) $id . ' THEN ' . (int) $position . ' ';
                $ids[] = (int) $id;
            }

            DB::statement(
                "UPDATE {$table} SET position = CASE id {$cases} END WHERE id IN (" . implode(',', $ids) . ')'
            );
        }
    }

    private function backfillCapacity(string $childTable, string $parentKey, string $parentTable, string $capacityColumn): void
    {
        if (!Schema::hasTable($childTable) || !Schema::hasTable($parentTable)) {
            return;
        }
        if (!Schema::hasColumn($parentTable, $capacityColumn)) {
            return;
        }

        $counts = DB::table($childTable)
            ->select($parentKey, DB::raw('COUNT(*) as total'))
            ->whereNotNull($parentKey)
            ->groupBy($parentKey)
            ->pluck('total', $parentKey);

        foreach ($counts as $parentId => $total) {
            DB::table($parentTable)
                ->where('id', $parentId)
                ->whereNull($capacityColumn)
                ->update([$capacityColumn => $total]);
        }
    }
};
