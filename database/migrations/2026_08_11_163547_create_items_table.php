<?php

use App\Models\Category;
use App\Models\User;
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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('contacts')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('phone_views')->default(0);
            $table->unsignedInteger('email_views')->default(0);
            $table->dateTime('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
