<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('roles', function (Blueprint $t) {
			$t->id();
			$t->string('name', 50);
			$t->string('slug', 50)->unique();
			$t->timestamps();
		});
		Schema::create('users', function (Blueprint $t) {
			$t->id();
			$t->foreignId('role_id')->constrained('roles')->restrictOnDelete();
			$t->string('name');
			$t->string('email')->unique();
			$t->string('phone', 20)->nullable();
			$t->timestamp('email_verified_at')->nullable();
			$t->string('password');
			$t->rememberToken();
			$t->timestamps();
		});
		Schema::create('password_reset_tokens', function (Blueprint $t) {
			$t->string('email')->primary();
			$t->string('token');
			$t->timestamp('created_at')->nullable();
		});
		Schema::create('tariffs', function (Blueprint $t) {
			$t->id();
			$t->string('name', 100);
			$t->text('description')->nullable();
			$t->decimal('price', 10, 2);
			$t->unsignedInteger('duration');
			$t->boolean('is_active')->default(true);
			$t->timestamps();
		});
		Schema::create('bookings', function (Blueprint $t) {
			$t->id();
			$t->foreignId('user_id')->constrained('users')->restrictOnDelete();
			$t->foreignId('tariff_id')->constrained('tariffs')->restrictOnDelete();
			$t->dateTime('start_at');
			$t->unsignedInteger('participants_count');
			$t->decimal('tariff_price', 10, 2);
			$t->unsignedInteger('tariff_duration');
			$t->string('status', 30)->default('pending');
			$t->text('comment')->nullable();
			$t->timestamps();
			$t->index([
				'start_at',
				'status',
			]);
		});
		Schema::create('booking_participants', function (Blueprint $t) {
			$t->id();
			$t->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
			$t->string('full_name');
			$t->string('phone', 20)->nullable();
			$t->timestamps();
		});
		Schema::create('payments', function (Blueprint $t) {
			$t->id();
			$t->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
			$t->decimal('amount', 10, 2);
			$t->string('method', 20);
			$t->string('status', 20)->default('pending');
			$t->string('transaction_id')->nullable();
			$t->dateTime('paid_at')->nullable();
			$t->timestamps();
			$t->index([
				'booking_id',
				'status',
			]);
		});
		Schema::create('karts', function (Blueprint $t) {
			$t->id();
			$t->string('number', 20)->unique();
			$t->string('model', 100)->nullable();
			$t->string('status', 30)->default('available');
			$t->text('description')->nullable();
			$t->timestamps();
		});
		Schema::create('maintenance_records', function (Blueprint $t) {
			$t->id();
			$t->foreignId('kart_id')->constrained('karts')->restrictOnDelete();
			$t->text('description');
			$t->date('maintenance_date');
			$t->decimal('cost', 10, 2)->nullable();
			$t->timestamps();
		});
		Schema::create('race_sessions', function (Blueprint $t) {
			$t->id();
			$t->foreignId('booking_id')->nullable()->unique()->constrained('bookings')->restrictOnDelete();
			$t->dateTime('started_at')->nullable();
			$t->dateTime('finished_at')->nullable();
			$t->string('status', 30)->default('planned');
			$t->timestamps();
		});
		Schema::create('race_participants', function (Blueprint $t) {
			$t->id();
			$t->foreignId('race_session_id')->constrained('race_sessions')->restrictOnDelete();
			$t->foreignId('booking_participant_id')->nullable()->constrained('booking_participants')->nullOnDelete();
			$t->foreignId('kart_id')->constrained('karts')->restrictOnDelete();
			$t->string('full_name');
			$t->string('status', 30)->default('registered');
			$t->timestamps();
			$t->unique([
				'race_session_id',
				'kart_id',
			]);
		});
		Schema::create('lap_results', function (Blueprint $t) {
			$t->id();
			$t->foreignId('race_participant_id')->constrained('race_participants')->restrictOnDelete();
			$t->unsignedInteger('lap_number');
			$t->decimal('lap_time', 8, 3);
			$t->timestamps();
			$t->unique([
				'race_participant_id',
				'lap_number',
			]);
		});
	}
	public function down(): void
	{
		foreach ([
			'lap_results',
			'race_participants',
			'race_sessions',
			'maintenance_records',
			'karts',
			'payments',
			'booking_participants',
			'bookings',
			'tariffs',
			'password_reset_tokens',
			'users',
			'roles',
		] as $table) {
			Schema::dropIfExists($table);
		}
	}
};
