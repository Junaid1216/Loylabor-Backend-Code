<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\BasicPayment\database\seeders\BasicPaymentInfoSeeder;
use Modules\BasicPayment\database\seeders\PaymentGatewaySeeder;
use Modules\Blog\database\seeders\BlogDatabaseSeeder;
use Modules\Coupon\database\seeders\CouponDatabaseSeeder;
use Modules\Currency\database\seeders\CurrencySeeder;
use Modules\CustomMenu\database\seeders\CustomMenuDatabaseSeeder;
use Modules\Faq\database\seeders\FaqDatabaseSeeder;
use Modules\Frontend\database\seeders\FrontendDatabaseSeeder;
use Modules\GlobalSetting\database\seeders\CustomPaginationSeeder;
use Modules\GlobalSetting\database\seeders\EmailTemplateSeeder;
use Modules\GlobalSetting\database\seeders\GlobalSettingInfoSeeder;
use Modules\GlobalSetting\database\seeders\SeoInfoSeeder;
use Modules\KnowYourClient\database\seeders\KnowYourClientDatabaseSeeder;
use Modules\Language\database\seeders\LanguageSeeder;
use Modules\PageBuilder\database\seeders\PageBuilderDatabaseSeeder;
use Modules\PaymentWithdraw\database\seeders\PaymentWithdrawDatabaseSeeder;
use Modules\Product\database\seeders\ProductDatabaseSeeder;
use Modules\Shipping\database\seeders\ShippingDatabaseSeeder;
use Modules\Tax\database\seeders\TaxDatabaseSeeder;
use Modules\Testimonial\database\seeders\TestimonialDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (false || cache()->has('fresh_install') && cache()->get('fresh_install')) {
            $this->call([
                GlobalSettingInfoSeeder::class,
                LanguageSeeder::class,
                CurrencySeeder::class,
                BasicPaymentInfoSeeder::class,
                PaymentGatewaySeeder::class,
                CustomPaginationSeeder::class,
                EmailTemplateSeeder::class,
                SeoInfoSeeder::class,
                RolePermissionSeeder::class,
                TaxDatabaseSeeder::class,
                PageBuilderDatabaseSeeder::class,
                CustomMenuDatabaseSeeder::class,
                FrontendDatabaseSeeder::class,
                ShippingDatabaseSeeder::class,
                AdminInfoSeeder::class,
                // InstallerDatabaseSeeder::class,
            ]);
        } else {
            $this->call([
                GlobalSettingInfoSeeder::class,
                LanguageSeeder::class,
                CurrencySeeder::class,
                BasicPaymentInfoSeeder::class,
                PaymentGatewaySeeder::class,
                CustomPaginationSeeder::class,
                EmailTemplateSeeder::class,
                SeoInfoSeeder::class,
                RolePermissionSeeder::class,
                KnowYourClientDatabaseSeeder::class,
                PaymentWithdrawDatabaseSeeder::class,
                FrontendDatabaseSeeder::class,
                AdminInfoSeeder::class,
                PageBuilderDatabaseSeeder::class,
                CustomMenuDatabaseSeeder::class,
                BlogDatabaseSeeder::class,
                FaqDatabaseSeeder::class,
                TestimonialDatabaseSeeder::class,
                CouponDatabaseSeeder::class,
                TaxDatabaseSeeder::class,
                ProductDatabaseSeeder::class,
                ShippingDatabaseSeeder::class,
                WishlistSeeder::class,
                // InstallerDatabaseSeeder::class,
            ]);
        }

        // if (app()->isLocal()) {
        //     $this->call([
        //         DummyDataSeeder::class,
        //     ]);
        // }

        if (app()->isLocal()) {
            $this->call([
                DummyDataSeeder::class,
            ]);
        }

        // if(app()->isProduction()){
        //     $this->call([
        //     ]);
        // }

        // run cache clear artisan command
        Artisan::call('cache:clear');
    }
}
