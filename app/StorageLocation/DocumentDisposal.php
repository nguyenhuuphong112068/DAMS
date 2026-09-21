<?php

namespace App\StorageLocation;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Support\Facades\DB;

// Huỷ hồ sơ: lưu bản chụp hồ sơ + vị trí vào document_disposals rồi xoá khỏi documents,
// nhờ vậy vị trí đang giữ trở thành vị trí trống ở mọi nơi (sơ đồ kho, chọn vị trí...).
// Gọi bên trong transaction, với $document đã khoá lockForUpdate.
class DocumentDisposal
{
    // Trả về mã vị trí vừa được trả trống.
    public static function dispose(object $document, string $reason, string $actor): ?string
    {
        $location = DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->where('locations.id', $document->location_id)
            ->select(
                'locations.code', 'locations.name',
                'warehouses.name as warehouse_name', 'shelves.name as shelf_name', 'tiers.name as tier_name'
            )
            ->first();

        $typeNames = DB::table('document_has_types')
            ->join('document_types', 'document_has_types.document_type_id', '=', 'document_types.id')
            ->where('document_has_types.document_id', $document->id)
            ->pluck('document_types.name')
            ->implode(', ');

        DB::table('document_disposals')->insert([
            'document_id'         => $document->id,
            'code'                => $document->code,
            'name'                => $document->name,
            'owner'               => $document->owner,
            'filepath'            => $document->filepath,
            'department_id'       => $document->department_id,
            'type_names'          => $typeNames !== '' ? mb_substr($typeNames, 0, 500) : null,
            'expired_date'        => $document->expired_date,
            'location_id'         => $document->location_id,
            'location_code'       => $location->code ?? null,
            'location_name'       => $location->name ?? null,
            'warehouse_name'      => $location->warehouse_name ?? null,
            'shelf_name'          => $location->shelf_name ?? null,
            'tier_name'           => $location->tier_name ?? null,
            'reason'              => $reason,
            'document_created_by' => $document->created_by,
            'document_created_at' => $document->created_at,
            'disposed_by'         => $actor,
            'disposed_at'         => now(),
        ]);

        DB::table('document_has_types')->where('document_id', $document->id)->delete();
        DB::table('documents')->where('id', $document->id)->delete();

        AuditTrialController::log(
            'Huỷ hồ sơ',
            'documents',
            $document->id,
            'Hồ sơ ' . $document->code . ' tại vị trí ' . ($location->code ?? $document->location_id),
            'Huỷ hồ sơ ' . $document->code . ' - ' . $document->name
                . ' | Trả vị trí trống: ' . ($location->code ?? $document->location_id)
                . ' | Lý do: ' . $reason
        );

        return $location->code ?? null;
    }
}
