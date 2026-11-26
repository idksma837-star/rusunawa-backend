<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('room_number');
            $table->unsignedTinyInteger('floor')->default(1);
            $table->unsignedInteger('price');
            $table->enum('status', ['available', 'occupied'])->default('available');
        });

        Schema::table('leases', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['start_date', 'end_date', 'status']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
            $table->dropColumn([
                'room_number',
                'floor',
                'price',
                'status',
            ]);
        });
    }
};
