<?php

use App\Artist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAlbumsToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->string('artist_type', 10)->default('App\\\Artist')->index();
            $table->string('description')->nullable();
            $table->boolean('local_only')->index()->default(false)->after('auto_update');

            $table->dropIndex('albums_name_artist_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('artist_type');
            $table->dropColumn('description');
        });
    }
}
