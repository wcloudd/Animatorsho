<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_submissions', function (Blueprint $table) {
            $table->string('attachment_disk')->nullable()->after('file_path');
            $table->string('attachment_path')->nullable()->after('attachment_disk');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime_type')->nullable()->after('attachment_original_name');
            $table->unsignedBigInteger('attachment_size_bytes')->nullable()->after('attachment_mime_type');
            $table->timestamp('attachment_deleted_at')->nullable()->after('attachment_size_bytes');
            $table->foreignId('attachment_deleted_by')->nullable()->after('attachment_deleted_at')->constrained('users')->nullOnDelete();

            $table->index(['attachment_deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('exercise_submissions', function (Blueprint $table) {
            $table->dropForeign(['attachment_deleted_by']);
            $table->dropIndex(['attachment_deleted_at', 'created_at']);
            $table->dropColumn([
                'attachment_disk',
                'attachment_path',
                'attachment_original_name',
                'attachment_mime_type',
                'attachment_size_bytes',
                'attachment_deleted_at',
                'attachment_deleted_by',
            ]);
        });
    }
};
