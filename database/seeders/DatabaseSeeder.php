<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\FoodRequest;
use App\Models\Location;
use App\Services\User\Factory\UserFactoryResolver;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        UserFactoryResolver::resolve('admin')->create([
            'name' => 'Admin User',
            'email' => 'admin@foodrescue.test',
            'password' => 'FoodBridgeDemo!2026',
            'phone' => '555-0100',
            'address' => '123 Admin St',
        ]);

        $beneficiary = UserFactoryResolver::resolve('beneficiary')->create([
            'name' => 'Jane Beneficiary',
            'email' => 'beneficiary@foodrescue.test',
            'password' => 'FoodBridgeDemo!2026',
            'phone' => '555-0101',
            'address' => '456 Needy Ave',
            'household_size' => 4,
            'dietary_needs' => 'Vegetarian',
            'income_level' => 'low',
            'emergency_contact' => '555-0102',
        ]);

        $donor = UserFactoryResolver::resolve('donor')->create([
            'name' => 'John Donor',
            'email' => 'donor@foodrescue.test',
            'password' => 'FoodBridgeDemo!2026',
            'phone' => '555-0103',
            'address' => '789 Generous Blvd',
        ]);

        UserFactoryResolver::resolve('driver')->create([
            'name' => 'Mike Driver',
            'email' => 'driver@foodrescue.test',
            'password' => 'FoodBridgeDemo!2026',
            'phone' => '555-0104',
            'address' => '321 Delivery Rd',
        ]);

        $categories = [
            ['name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
            ['name' => 'Bakery', 'description' => 'Bread, pastries, baked goods'],
            ['name' => 'Dairy', 'description' => 'Milk, cheese, yogurt'],
            ['name' => 'Prepared Meals', 'description' => 'Ready-to-eat meals'],
            ['name' => 'Canned Goods', 'description' => 'Non-perishable canned items'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $vegetableDonation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Fresh seasonal vegetable boxes',
            'description' => 'Mixed boxes of carrots, leafy greens, tomatoes and capsicum. Produce is fresh and packed for same-day collection.',
            'category_id' => Category::where('name', 'Fruits & Vegetables')->value('id'),
            'quantity' => 12,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(4)->toDateString(),
            'pickup_address' => config('foodrescue.food_bank_address'),
            'pickup_time' => null,
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'food_bank',
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Artisan bread and breakfast pastries',
            'description' => 'End-of-day bakery surplus packed in food-safe bags. Contains wheat, dairy and possible traces of nuts.',
            'category_id' => Category::where('name', 'Bakery')->value('id'),
            'quantity' => 30,
            'unit' => 'items',
            'expiry_date' => now()->addDays(2)->toDateString(),
            'pickup_address' => config('foodrescue.food_bank_address'),
            'pickup_time' => null,
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'food_bank',
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Family-size prepared meals',
            'description' => 'Chilled vegetable pasta meals prepared today and sealed in labelled trays. Refrigerated collection required.',
            'category_id' => Category::where('name', 'Prepared Meals')->value('id'),
            'quantity' => 18,
            'unit' => 'trays',
            'expiry_date' => now()->addDays(3)->toDateString(),
            'pickup_address' => config('foodrescue.food_bank_address'),
            'pickup_time' => null,
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'food_bank',
        ]);

        FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $vegetableDonation->id,
            'quantity_requested' => 2,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Food support requested for a household of four, with a preference for vegetarian meals and fresh produce.',
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '456 Needy Ave',
        ]);

        DonationItem::create([
            'donation_id' => $vegetableDonation->id,
            'item_name' => 'Organic Carrots',
            'quantity' => 4,
            'unit' => 'boxes',
        ]);
        DonationItem::create([
            'donation_id' => $vegetableDonation->id,
            'item_name' => 'Fresh Spinach',
            'quantity' => 4,
            'unit' => 'boxes',
        ]);
        DonationItem::create([
            'donation_id' => $vegetableDonation->id,
            'item_name' => 'Ripe Tomatoes',
            'quantity' => 4,
            'unit' => 'boxes',
        ]);

        Location::create([
            'address' => config('foodrescue.food_bank_address', 'Community Food Bank Central Distribution Hub, 100 Charity Lane'),
            'latitude' => 3.139003,
            'longitude' => 101.686855,
            'type' => 'donation',
            'reference_id' => $vegetableDonation->id,
        ]);

        Location::create([
            'address' => '456 Needy Ave',
            'latitude' => 3.141200,
            'longitude' => 101.691200,
            'type' => 'delivery',
            'reference_id' => 1,
        ]);
    }
}
