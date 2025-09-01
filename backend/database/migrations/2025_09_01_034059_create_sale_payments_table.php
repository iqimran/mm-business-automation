<?php

use App\Enums\PaymentMethod;
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
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_sale_id')->constrained('car_sales')->onDelete('cascade');
            $table->enum('payment_method',array_column(PaymentMethod::cases(), 'value'))->default(PaymentMethod::CASH->value)->index();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date')->index();
            $table->enum('status', array_column(PaymentStatus::cases(), 'value'))->default(PaymentStatus::PENDING->value)->index();
            $table->string('reference_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['car_sale_id', 'payment_date']);
            $table->index(['status', 'payment_date']);
            $table->index(['payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
