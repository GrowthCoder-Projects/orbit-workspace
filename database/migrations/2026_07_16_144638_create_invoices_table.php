<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('brand_prefix')->nullable();
            $table->string('status')->default('draft'); // draft, sent, overdue, paid
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('currency')->default('IDR');

            // Totals
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->string('discount_type')->default('fixed'); // fixed, percentage
            $table->decimal('total', 15, 2)->default(0.00);

            // Customization Options
            $table->string('template_name')->default('modern');
            $table->string('color_accent')->default('#3b82f6');
            $table->string('logo_path')->nullable();
            $table->string('font_family')->default('Helvetica');
            $table->string('spacing')->default('cozy'); // compact, cozy, spacious
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('notes')->nullable();

            // Payment Integration
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('finance_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
