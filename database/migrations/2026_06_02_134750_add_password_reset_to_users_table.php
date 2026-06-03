// database/migrations/2024_01_01_000003_add_password_reset_to_users_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reset_password_token')->nullable()->after('otp');
            $table->timestamp('reset_token_expires_at')->nullable()->after('reset_password_token');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reset_password_token', 'reset_token_expires_at']);
        });
    }
};