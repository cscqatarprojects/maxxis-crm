<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();

        DB::table('attributes')
            ->insert([
                [
                    'code'            => 'is_wholesale',
                    'name'            => trans('installer::app.seeders.attributes.persons.is-wholesale'),
                    'type'            => 'boolean',
                    'entity_type'     => 'persons',
                    'lookup_type'     => null,
                    'validation'      => null,
                    'sort_order'      => '7',
                    'is_required'     => '0',
                    'is_unique'       => '0',
                    'quick_add'       => '0',
                    'is_user_defined' => '0',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('attributes')
            ->where('entity_type', 'persons')
            ->where('code', 'is_wholesale')
            ->delete();
    }
};
