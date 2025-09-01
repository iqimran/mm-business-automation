<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CarStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('model')->index();
            $table->integer('year')->index();
            $table->string('license_plate')->unique();
            $table->string('color')->nullable()->index();
            $table->unsignedInteger('mileage')->default(0);
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('current_value', 12, 2)->nullable();

            $table->date('purchase_date')->nullable()->index();
            $table->date('registration_date')->nullable()->index();
            $table->date('fitness_date')->nullable()->index();
            $table->date('tax_token_date')->nullable()->index();
            $table->date('insurance_expiry')->nullable()->index();
            $table->date('registration_expiry')->nullable()->index();
            $table->date('last_service_date')->nullable()->index();
            $table->date('next_service_due')->nullable()->index();

            $table->text('notes')->nullable();
            $table->enum('status', array_column(CarStatus::cases(), 'value'))->default(CarStatus::ACTIVE->value)->index();
            $table->json('images')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['model', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
