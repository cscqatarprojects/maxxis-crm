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
        Schema::table('web_forms', function (Blueprint $table) {
            $table->integer('lead_type_id')->unsigned()->nullable();

            $table->foreign('lead_type_id')->references('id')->on('lead_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_forms', function (Blueprint $table) {
            $table->dropForeign(['lead_type_id']);

            $table->dropColumn('lead_type_id');
        });
    }
};
