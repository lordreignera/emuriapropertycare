<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_quotations', function (Blueprint $table) {
            $table->id();

            // ── Relationships ──────────────────────────────────────────────
            $table->foreignId('inspection_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');

            // ── Identification ─────────────────────────────────────────────
            $table->string('quote_number')->unique();

            // ── Workflow Status ────────────────────────────────────────────
            // draft       → admin is building it
            // shared      → admin shared it with client
            // client_reviewing → client has seen it (first open)
            // client_responded → client submitted their selection
            // approved    → admin acknowledged client response, assessment finalised
            // rejected    → client rejected all findings / declined
            // expired     → client did not respond before expires_at
            $table->enum('status', [
                'draft',
                'shared',
                'client_reviewing',
                'client_responded',
                'approved',
                'rejected',
                'expired',
            ])->default('draft');

            // ── Findings Snapshot (immutable at time of sharing) ──────────
            // Full copy of all PHAR findings so the quotation is self-contained
            // even if findings are later edited. Each element mirrors the
            // phar_findings row structure.
            $table->json('findings_snapshot')->nullable();

            // ── Client Selection ───────────────────────────────────────────
            // Array of phar_findings IDs the client approved for remediation
            $table->json('approved_finding_ids')->nullable();
            // Array of phar_findings IDs the client chose to defer
            $table->json('deferred_finding_ids')->nullable();

            $table->text('client_notes')->nullable();
            $table->timestamp('client_responded_at')->nullable();

            // ── Computed Totals (from approved findings only) ──────────────
            $table->decimal('approved_labour_cost', 10, 2)->default(0);
            $table->decimal('approved_material_cost', 10, 2)->default(0);
            $table->decimal('approved_bdc_cost', 10, 2)->default(0);
            $table->decimal('approved_total', 10, 2)->default(0);

            // ── Admin Fields ───────────────────────────────────────────────
            $table->text('admin_notes')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->date('valid_until')->nullable();

            $table->timestamps();

            // ── Indexes ────────────────────────────────────────────────────
            $table->index('inspection_id');
            $table->index('property_id');
            $table->index('status');
            $table->index('shared_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_quotations');
    }
};
