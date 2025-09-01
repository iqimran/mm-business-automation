<?php

use App\Enums\SaleStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('car_sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->decimal('sale_price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2);
            $table->date('sale_date')->index();
            $table->enum('payment_status', array_column(PaymentStatus::cases(), 'value'))->default(PaymentStatus::PENDING->value)->index();
            $table->enum('sale_status', array_column(SaleStatus::cases(), 'value'))->default(SaleStatus::DRAFT->value)->index();
            $table->decimal('commission_rate', 5, 2)->default(0); // Percentage
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('contract_terms')->nullable();
            $table->json('warranty_terms')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['car_id', 'sale_status']);
            $table->index(['client_id', 'payment_status']);
            $table->index(['sale_date', 'sale_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_sales');
    }
};
