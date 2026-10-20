<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('timezone')->default('America/Chicago');
            $table->json('hours');
            $table->unsignedSmallInteger('slot_minutes')->default(60);
            $table->unsignedSmallInteger('notice_hours')->default(2);
            $table->string('intake_token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('business_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('label');
            $table->text('value');
            $table->string('source');
            $table->string('confidence')->default('medium');
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('public_token', 64)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->default('website');
            $table->text('message');
            $table->string('service_requested')->nullable();
            $table->string('location')->nullable();
            $table->string('intent')->nullable();
            $table->string('urgency')->nullable();
            $table->string('status')->default('waiting');
            $table->string('awaiting')->nullable();
            $table->json('offered_slots')->nullable();
            $table->text('qualification_notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('booked');
            $table->string('summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('business_facts');
        Schema::dropIfExists('businesses');
    }
};
