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
        Schema::create('car_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
            $table->foreignId('expense_category_id')->constrained('car_expense_categories')->onDelete('restrict');

            $table->date('expense_date')->index();
            $table->decimal('amount', 10, 2)->index();
            $table->string('title')->nullable();
            $table->text('note')->nullable();
            $table->string('document')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['car_id', 'expense_date']);
            $table->index(['expense_category_id', 'expense_date']);
            $table->index(['car_id', 'expense_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_expenses');
    }
};
