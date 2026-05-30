<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_url')->nullable()->after('phone');
            $table->string('banner_image_url')->nullable()->after('profile_photo_url');
            $table->string('banner_color', 7)->default('#101010')->after('banner_image_url');
            $table->string('github_url')->nullable()->after('banner_color');
            $table->string('github_repository_url')->nullable()->after('github_url');

            $table->index('github_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['github_url']);
            $table->dropColumn([
                'profile_photo_url',
                'banner_image_url',
                'banner_color',
                'github_url',
                'github_repository_url',
            ]);
        });
    }
};
