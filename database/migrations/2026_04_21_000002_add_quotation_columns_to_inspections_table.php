<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // FK to the active quotation (set once the quotation is shared/approved)
            $table->foreignId('active_quotation_id')
                ->nullable()
                ->after('client_acknowledgment')
                ->constrained('inspection_quotations')
                ->nullOnDelete();

            // Tracks where the quotation step sits in the workflow:
            // null          → quotation not yet created
            // draft         → admin building quotation
            // shared        → admin shared with client, awaiting response
            // client_responded → client submitted selection
            // approved      → totals recalculated, assessment can be completed
            // skipped       → admin bypassed quotation step (admin-only override)
            $table->string('quotation_status', 30)
                ->nullable()
                ->after('active_quotation_id');

            $table->timestamp('quotation_shared_at')
                ->nullable()
                ->after('quotation_status');

            $table->timestamp('quotation_approved_at')
                ->nullable()
                ->after('quotation_shared_at');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropForeign(['active_quotation_id']);
            $table->dropColumn([
                'active_quotation_id',
                'quotation_status',
                'quotation_shared_at',
                'quotation_approved_at',
            ]);
        });
    }
};
