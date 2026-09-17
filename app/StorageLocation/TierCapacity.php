<?php

namespace App\StorageLocation;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Sức chứa của tầng là con số, nhưng vị trí là bản ghi thật: hồ sơ, lịch sử di chuyển và
// nhãn đã in đều trỏ vào id/mã của nó. Nên khi sức chứa giảm ta khoá ô thừa chứ không xoá,
// và khi tăng lại thì mở đúng dải đã khoá.
class TierCapacity
{
    // Vị trí đang chứa hồ sơ mà lại nằm ngoài sức chứa mới. Khoá chúng sẽ tạo ra hồ sơ nằm
    // ở ô ngưng dùng, nên nơi gọi phải chặn thao tác và bắt chuyển hồ sơ đi trước.
    public static function occupiedAbove(int $tierId, int $max): Collection
    {
        return DB::table('locations as l')
            ->join('documents as d', 'd.location_id', '=', 'l.id')
            ->where('l.tier_id', $tierId)
            ->where('l.position', '>', $max)
            ->orderBy('l.position')
            ->pluck('l.code')
            ->unique()
            ->values();
    }

    public static function sync(int $tierId, ?int $oldMax, ?int $newMax): array
    {
        $changes = ['created' => 0, 'locked' => 0, 'unlocked' => 0];

        if ($newMax === null) {
            return $changes;
        }

        $tier = DB::table('tiers')->where('id', $tierId)->first();
        $actor = session('user')['fullName'] ?? 'Admin';
        $now = now();

        $taken = DB::table('locations')
            ->where('tier_id', $tierId)
            ->whereNotNull('position')
            ->pluck('position')
            ->all();

        $missing = [];
        for ($position = 1; $position <= $newMax; $position++) {
            if (!in_array($position, $taken)) {
                $missing[$position] = $tier->code . '.' . str_pad($position, 3, '0', STR_PAD_LEFT);
            }
        }

        if ($missing) {
            // Mã vị trí unique toàn bảng, dữ liệu cũ có thể đã chiếm mã nên bỏ qua để không vỡ ràng buộc.
            $usedCodes = DB::table('locations')->whereIn('code', $missing)->pluck('code')->all();

            $rows = [];
            foreach ($missing as $position => $code) {
                if (in_array($code, $usedCodes, true)) {
                    continue;
                }

                $rows[] = [
                    'code'          => $code,
                    'name'          => 'Vị trí ' . str_pad($position, 3, '0', STR_PAD_LEFT),
                    'department_id' => $tier->department_id,
                    'warehouse_id'  => $tier->warehouse_id,
                    'shelf_id'      => $tier->shelf_id,
                    'tier_id'       => $tierId,
                    'position'      => $position,
                    'status_id'     => 1,
                    'active'        => 1,
                    'created_by'    => $actor,
                    'created_at'    => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('locations')->insert($chunk);
            }

            $changes['created'] = count($rows);
        }

        // Chỉ mở lại đúng dải vừa nằm ngoài sức chứa cũ, để không vô tình mở những ô
        // người dùng đã chủ động khoá vì lý do khác.
        if ($oldMax !== null && $newMax > $oldMax) {
            $changes['unlocked'] = DB::table('locations')
                ->where('tier_id', $tierId)
                ->whereBetween('position', [$oldMax + 1, $newMax])
                ->where('status_id', '<>', 1)
                ->update([
                    'status_id'  => 1,
                    'active'     => 1,
                    'updated_at' => $now,
                    'updated_by' => $actor,
                ]);
        }

        $changes['locked'] = DB::table('locations')
            ->where('tier_id', $tierId)
            ->where('position', '>', $newMax)
            ->where('status_id', 1)
            ->update([
                'status_id'  => 0,
                'active'     => 0,
                'updated_at' => $now,
                'updated_by' => $actor,
            ]);

        if (array_sum($changes)) {
            AuditTrialController::log(
                'Cập nhật sức chứa tầng',
                'tiers',
                $tierId,
                'Số vị trí tối đa: ' . ($oldMax ?? '-'),
                'Số vị trí tối đa: ' . $newMax . ' (tạo ' . $changes['created']
                    . ', khoá ' . $changes['locked'] . ', mở lại ' . $changes['unlocked'] . ')'
            );
        }

        return $changes;
    }

    public static function describe(array $changes): string
    {
        $parts = [];
        if ($changes['created']) {
            $parts[] = 'tạo ' . $changes['created'] . ' vị trí';
        }
        if ($changes['locked']) {
            $parts[] = 'khoá ' . $changes['locked'] . ' vị trí vượt sức chứa';
        }
        if ($changes['unlocked']) {
            $parts[] = 'mở lại ' . $changes['unlocked'] . ' vị trí';
        }

        return $parts ? ' Đã ' . implode(', ', $parts) . '.' : '';
    }
}
