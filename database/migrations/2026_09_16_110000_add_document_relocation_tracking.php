<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vị trí đang in trên nhãn của hồ sơ. Nhãn cần in lại khi khác location_id,
        // nên chuyển đi rồi chuyển về chỗ cũ thì tự hết cần in lại.
        if (!Schema::hasColumn('documents', 'labeled_location_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedBigInteger('labeled_location_id')->nullable()->after('location_id');
                $table->index('labeled_location_id');
            });

            // Nhãn của hồ sơ hiện có coi như đang đúng vị trí.
            DB::table('documents')->update(['labeled_location_id' => DB::raw('location_id')]);
        }

        if (!Schema::hasTable('document_moves')) {
            Schema::create('document_moves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('document_id');
                $table->unsignedBigInteger('from_location_id')->nullable();
                $table->unsignedBigInteger('to_location_id');
                $table->string('moved_by')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('document_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_moves');

        if (Schema::hasColumn('documents', 'labeled_location_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropIndex(['labeled_location_id']);
                $table->dropColumn('labeled_location_id');
            });
        }
    }
};
