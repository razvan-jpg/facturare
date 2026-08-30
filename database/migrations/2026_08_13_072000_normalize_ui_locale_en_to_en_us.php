<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('ui_locale', 'en')->update(['ui_locale' => 'en_US']);
        DB::table('users')->where('ui_locale', 'zh')->update(['ui_locale' => 'zh_CN']);
    }

    public function down(): void
    {
        DB::table('users')->where('ui_locale', 'en_US')->update(['ui_locale' => 'en']);
        DB::table('users')->where('ui_locale', 'zh_CN')->update(['ui_locale' => 'zh']);
    }
};
