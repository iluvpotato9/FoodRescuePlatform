<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\DeliverySchedule;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\FoodRequest;
use App\Models\Location;
use App\Models\PickupSchedule;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Donation\Strategy\DonationSortStrategyResolver;
use App\Services\Donation\Strategy\ExpiryDateSortStrategy;
use App\Services\FoodRequest\FoodRequestService;
use App\Services\Scheduling\SchedulingService;
use App\Services\User\Factory\DonorUserFactory;
use App\Services\User\Factory\UserFactoryResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_uses_secure_rules_and_redirects_to_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Donor',
            'email' => 'DONOR@example.test',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123',
            'role' => 'donor',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.test',
            'role' => 'donor',
        ]);
    }

    public function test_public_registration_rejects_weak_passwords_and_admin_role(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Unsafe Account',
            'email' => 'unsafe@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'terms' => '1',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password', 'role']);
        $this->assertDatabaseMissing('users', ['email' => 'unsafe@example.test']);
    }

    public function test_login_regenerates_session_and_redirects_to_dashboard(): void
    {
        $user = User::forceCreate([
            'name' => 'Beneficiary',
            'email' => 'beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);

        $response = $this->post('/login', [
            'email' => 'beneficiary@example.test',
            'password' => 'StrongPassword!123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_design_pattern_resolvers_return_designated_implementations(): void
    {
        $this->assertInstanceOf(
            DonorUserFactory::class,
            UserFactoryResolver::resolve('donor')
        );
        $this->assertInstanceOf(
            ExpiryDateSortStrategy::class,
            DonationSortStrategyResolver::resolve('expiry_date')
        );

        $beneficiary = User::forceCreate([
            'name' => 'Beneficiary',
            'email' => 'beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $request = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Household food support is required.',
        ]);

        $updated = app(FoodRequestService::class)->updateStatus($request, 'approve');
        $this->assertSame('approved', $updated->status);
    }

    public function test_observer_scheduling_service_updates_pickup_status(): void
    {
        $donor = User::forceCreate([
            'name' => 'Donor',
            'email' => 'donor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $driver = User::forceCreate([
            'name' => 'Driver',
            'email' => 'driver@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'driver',
        ]);
        $beneficiary = User::forceCreate([
            'name' => 'Beneficiary',
            'email' => 'receiver@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $category = Category::create(['name' => 'Produce']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Vegetable boxes',
            'category_id' => $category->id,
            'quantity' => 4,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(3),
            'pickup_address' => '1 Market Street',
            'status' => 'available',
            'is_active' => true,
        ]);

        $service = app(SchedulingService::class);
        $pickup = $service->createPickup([
            'donation_id' => $donation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => now()->addDay(),
        ]);
        $updated = $service->updatePickupStatus($pickup, 'picked_up');

        $this->assertSame('picked_up', $updated->status);
        $this->assertSame('picked_up', $donation->fresh()->status);
        $service->updatePickupStatus($pickup->fresh(), 'completed');
        $this->assertSame('food_bank', $donation->fresh()->collection_location);

        $foodRequest = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 1,
            'request_date' => now()->toDateString(),
            'status' => 'reserved',
            'notes' => 'Household food support is required.',
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '5 Community Road',
            'ready_at' => now(),
            'collection_deadline' => now()->addDays(3)->endOfDay(),
            'fulfillment_scheduled_at' => now()->addDays(2),
        ]);
        $reservation = Reservation::create([
            'request_id' => $foodRequest->id,
            'donation_id' => $donation->id,
            'quantity_reserved' => 1,
            'reservation_date' => now()->toDateString(),
        ]);
        $delivery = $service->createDelivery([
            'reservation_id' => $reservation->id,
            'pickup_schedule_id' => $pickup->id,
            'driver_id' => $driver->id,
        ]);

        $service->updateDeliveryStatus($delivery, 'completed');
        $this->assertSame('completed', $foodRequest->fresh()->status);
    }

    public function test_reserving_food_with_a_donor_notifies_admin_to_schedule_pickup(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'admin',
        ]);
        $beneficiary = User::forceCreate([
            'name' => 'Beneficiary',
            'email' => 'beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $donor = User::forceCreate([
            'name' => 'Donor',
            'email' => 'donor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $category = Category::create(['name' => 'Prepared food']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Prepared meals',
            'category_id' => $category->id,
            'quantity' => 8,
            'unit' => 'trays',
            'expiry_date' => now()->addDays(2),
            'pickup_address' => '1 Donor Road',
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'donor',
        ]);
        $foodRequest = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 2,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Food support is needed this week.',
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '9 Beneficiary Street',
        ]);

        $this->actingAs($admin);
        app(FoodRequestService::class)->approveRequest($foodRequest);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('schedule_pickup', $admin->notifications()->first()->data['action']);
    }

    public function test_approved_food_is_picked_up_before_beneficiary_schedules_within_three_days(): void
    {
        Carbon::setTestNow('2026-08-17 10:00:00');

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'flow-admin@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'admin',
        ]);
        $beneficiary = User::forceCreate([
            'name' => 'Beneficiary',
            'email' => 'flow-beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $donor = User::forceCreate([
            'name' => 'Donor',
            'email' => 'flow-donor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $driver = User::forceCreate([
            'name' => 'Available Driver',
            'email' => 'flow-driver@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'driver',
        ]);
        $category = Category::create(['name' => 'Flow produce']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Fruit boxes',
            'category_id' => $category->id,
            'quantity' => 6,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(5),
            'pickup_address' => '20 Donor Street',
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'donor',
        ]);

        $foodRequestService = app(FoodRequestService::class);
        $foodRequest = $foodRequestService->create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 2,
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '8 Recipient Road',
        ]);

        $this->actingAs($admin);
        $foodRequestService->approveRequest($foodRequest);
        $this->assertSame('approved', $foodRequest->fresh()->status);
        $this->assertDatabaseHas('reservations', ['request_id' => $foodRequest->id]);

        $schedulingService = app(SchedulingService::class);
        $pickup = $schedulingService->createPickup([
            'donation_id' => $donation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => now()->addHour(),
        ]);
        $schedulingService->updatePickupStatus($pickup, 'completed');

        $readyRequest = $foodRequest->fresh();
        $this->assertSame('reserved', $readyRequest->status);
        $this->assertNotNull($readyRequest->ready_at);
        $this->assertNotNull($readyRequest->collection_deadline);
        $this->assertSame('choose_fulfillment_time', $beneficiary->notifications()->latest()->first()->data['action']);

        $chosenTime = $readyRequest->ready_at->copy()->addDay()->startOfDay()->addHours(10);
        $foodRequestService->scheduleFulfillment($readyRequest, $chosenTime->toDateTimeString());
        $this->assertEquals($chosenTime, $foodRequest->fresh()->fulfillment_scheduled_at);

        $this->actingAs($beneficiary)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Time confirmed');
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Assign home delivery drivers')
            ->assertSee($beneficiary->name);
        $this->actingAs($donor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Update quantity and expiry');
        Carbon::setTestNow();
    }

    public function test_donor_listing_uses_food_bank_dropoff_without_requesting_private_address(): void
    {
        $donor = User::forceCreate([
            'name' => 'Dropoff Donor',
            'email' => 'dropoff@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
            'address' => 'Private donor address',
        ]);
        $category = Category::create(['name' => 'Dropoff food']);

        $this->actingAs($donor)->post('/donations', [
            'title' => 'Invalid decimal boxes',
            'category_id' => $category->id,
            'quantity' => 1.5,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(4)->toDateString(),
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($donor)->post('/donations', [
            'title' => 'Food bank dropoff boxes',
            'category_id' => $category->id,
            'quantity' => 5,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(4)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'pickup_address' => config('foodrescue.food_bank_address'),
            'collection_location' => 'food_bank',
        ]);
        $this->assertDatabaseMissing('donations', ['pickup_address' => 'Private donor address']);
    }

    public function test_fulfillment_time_must_be_within_food_bank_operating_hours(): void
    {
        Carbon::setTestNow('2026-08-17 10:00:00');

        $beneficiary = User::forceCreate([
            'name' => 'Hours Beneficiary',
            'email' => 'hours-beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $donor = User::forceCreate([
            'name' => 'Hours Donor',
            'email' => 'hours-donor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $category = Category::create(['name' => 'Hours food']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Operating hours meals',
            'category_id' => $category->id,
            'quantity' => 3,
            'unit' => 'trays',
            'expiry_date' => now()->addDays(5),
            'pickup_address' => config('foodrescue.food_bank_address'),
            'status' => 'reserved',
            'is_active' => true,
            'collection_location' => 'food_bank',
        ]);
        $foodRequest = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 1,
            'request_date' => now()->toDateString(),
            'status' => 'reserved',
            'fulfillment_method' => 'food_bank_pickup',
            'ready_at' => now(),
            'collection_deadline' => now()->addDays(3)->endOfDay(),
        ]);

        $this->actingAs($beneficiary)->post('/requests', [
            'donation_id' => $donation->id,
            'quantity_requested' => -1,
            'fulfillment_method' => 'food_bank_pickup',
        ])->assertSessionHasErrors('quantity_requested');
        $this->actingAs($beneficiary)->post('/requests', [
            'donation_id' => $donation->id,
            'quantity_requested' => 1.5,
            'fulfillment_method' => 'food_bank_pickup',
        ])->assertSessionHasErrors('quantity_requested');

        try {
            app(FoodRequestService::class)->scheduleFulfillment($foodRequest, '2026-08-18 20:00:00');
            $this->fail('An after-hours selection should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('9:00 AM', $exception->getMessage());
        }

        $updated = app(FoodRequestService::class)->scheduleFulfillment($foodRequest, '2026-08-18 10:00:00');
        $this->assertSame('2026-08-18 10:00:00', $updated->fulfillment_scheduled_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_driver_cannot_update_a_future_assignment(): void
    {
        Carbon::setTestNow('2026-08-17 10:00:00');
        $driver = User::forceCreate([
            'name' => 'Future Driver',
            'email' => 'future-driver@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'driver',
        ]);
        $donor = User::forceCreate([
            'name' => 'Future Donor',
            'email' => 'future-donor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $category = Category::create(['name' => 'Future food']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Future assignment',
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(5),
            'pickup_address' => config('foodrescue.food_bank_address'),
            'status' => 'available',
            'is_active' => true,
            'collection_location' => 'food_bank',
        ]);
        $pickup = PickupSchedule::create([
            'donation_id' => $donation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => '2026-08-19 10:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($driver)
            ->patch(route('pickups.status', $pickup), ['status' => 'picked_up'])
            ->assertSessionHasErrors('status');
        $this->assertSame('scheduled', $pickup->fresh()->status);

        $beneficiary = User::forceCreate([
            'name' => 'Future Beneficiary',
            'email' => 'future-beneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $foodRequest = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 1,
            'request_date' => now()->toDateString(),
            'status' => 'reserved',
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '99 Future Road',
            'fulfillment_scheduled_at' => '2026-08-19 10:00:00',
        ]);
        $reservation = Reservation::create([
            'request_id' => $foodRequest->id,
            'donation_id' => $donation->id,
            'quantity_reserved' => 1,
            'reservation_date' => now()->toDateString(),
        ]);
        $deliverySchedule = DeliverySchedule::create([
            'reservation_id' => $reservation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => '2026-08-19 10:00:00',
            'status' => 'scheduled',
        ]);
        $delivery = Delivery::create([
            'reservation_id' => $reservation->id,
            'delivery_schedule_id' => $deliverySchedule->id,
        ]);

        $this->actingAs($driver)
            ->patch(route('deliveries.status', $delivery), ['status' => 'in_transit'])
            ->assertSessionHasErrors('status');
        $this->assertSame('scheduled', $deliverySchedule->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_beneficiary_can_update_profile_and_delete_account_data(): void
    {
        $beneficiary = User::forceCreate([
            'name' => 'Profile Beneficiary',
            'email' => 'profile@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $foodRequest = FoodRequest::create([
            'user_id' => $beneficiary->id,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'fulfillment_method' => 'food_bank_pickup',
        ]);

        $this->actingAs($beneficiary)->patch('/profile', [
            'name' => 'Updated Beneficiary',
            'email' => 'UPDATED@example.test',
            'phone' => '555-0100',
            'address' => '10 Updated Road',
            'household_size' => 4,
            'dietary_needs' => 'Nut allergy',
            'income_level' => 'Support requested',
            'emergency_contact' => 'Alex 555-0101',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $beneficiary->id, 'email' => 'updated@example.test']);
        $this->assertDatabaseHas('beneficiary_profiles', ['user_id' => $beneficiary->id, 'household_size' => 4]);

        $this->actingAs($beneficiary->fresh())->delete('/profile', [
            'password' => 'StrongPassword!123',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['id' => $beneficiary->id]);
        $this->assertDatabaseMissing('food_requests', ['id' => $foodRequest->id]);
        $this->assertDatabaseMissing('beneficiary_profiles', ['user_id' => $beneficiary->id]);
    }

    public function test_donation_status_update_endpoint_via_api(): void
    {
        $donor = User::forceCreate([
            'name' => 'Status Donor',
            'email' => 'statusdonor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $category = Category::create(['name' => 'Status Test Category', 'description' => 'Test']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Canned Beans',
            'category_id' => $category->id,
            'quantity' => 10,
            'unit' => 'items',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'pickup_address' => 'Food Bank Distribution Center',
            'status' => 'available',
            'is_active' => true,
        ]);

        $response = $this->actingAs($donor, 'sanctum')->putJson("/api/donations/{$donation->id}/status", [
            'status' => 'reserved',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Donation status updated.']);
        $this->assertSame('reserved', $donation->fresh()->status);
    }

    public function test_module_1_approved_food_requests_web_service(): void
    {
        $beneficiary = User::forceCreate([
            'name' => 'Approved Beneficiary',
            'email' => 'apprbeneficiary@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $category = Category::create(['name' => 'Appr Category', 'description' => 'Test']);
        $donation = Donation::create([
            'donor_id' => $beneficiary->id,
            'title' => 'Fresh Bread',
            'category_id' => $category->id,
            'quantity' => 20,
            'unit' => 'items',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'pickup_address' => 'Food Bank Distribution Center',
            'status' => 'available',
            'is_active' => true,
        ]);
        FoodRequest::create([
            'user_id' => $beneficiary->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 3,
            'request_date' => now()->toDateString(),
            'status' => 'approved',
            'fulfillment_method' => 'home_delivery',
            'delivery_address' => '123 Test St',
        ]);

        // Negative scenario: missing requestID
        $this->getJson('/api/webservice/requests/approved')
            ->assertStatus(422)
            ->assertJson(['status' => 'F']);

        // Positive scenario
        $res = $this->getJson('/api/webservice/requests/approved?requestID=REQ-APP-001')
            ->assertOk()
            ->assertJson([
                'status' => 'S',
                'requestID' => 'REQ-APP-001',
            ]);

        $this->assertNotEmpty($res->json('data'));
    }

    public function test_module_2_delivery_status_web_service(): void
    {
        $driver = User::forceCreate([
            'name' => 'Test Driver',
            'email' => 'driverws@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'driver',
            'phone' => '555-0999',
        ]);
        $donor = User::forceCreate([
            'name' => 'Donor WS',
            'email' => 'donorws@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $category = Category::create(['name' => 'Delivery WS Category', 'description' => 'Test']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Delivery WS Bread',
            'category_id' => $category->id,
            'quantity' => 15,
            'unit' => 'items',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'pickup_address' => 'Food Bank Distribution Center',
            'status' => 'available',
            'is_active' => true,
        ]);
        $request = FoodRequest::create([
            'user_id' => $donor->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 2,
            'request_date' => now()->toDateString(),
            'status' => 'reserved',
            'fulfillment_method' => 'home_delivery',
        ]);
        $reservation = Reservation::create([
            'request_id' => $request->id,
            'donation_id' => $donation->id,
            'quantity_reserved' => 2,
            'reservation_date' => now()->toDateString(),
        ]);
        $deliverySchedule = DeliverySchedule::create([
            'reservation_id' => $reservation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => now()->addDay(),
            'status' => 'scheduled',
            'notes' => 'Ring bell on arrival',
        ]);
        $delivery = Delivery::create([
            'reservation_id' => $reservation->id,
            'delivery_schedule_id' => $deliverySchedule->id,
        ]);

        // Negative test: missing parameters
        $this->getJson('/api/webservice/deliveries/status?requestID=REQ-DEL-001')
            ->assertStatus(422)
            ->assertJson(['status' => 'F']);

        // Positive test
        $this->getJson("/api/webservice/deliveries/status?requestID=REQ-DEL-002&delivery_id={$delivery->id}")
            ->assertOk()
            ->assertJson([
                'status' => 'S',
                'requestID' => 'REQ-DEL-002',
                'data' => [
                    'delivery_id' => $delivery->id,
                    'status' => 'scheduled',
                    'driver_name' => 'Test Driver',
                ],
            ]);
    }

    public function test_module_4_user_profile_web_service(): void
    {
        $user = User::forceCreate([
            'name' => 'Syed Profile Test',
            'email' => 'syedprofile@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
            'phone' => '555-8888',
        ]);

        // Negative test: missing requestID
        $this->getJson("/api/webservice/users/{$user->id}/profile")
            ->assertStatus(422)
            ->assertJson(['status' => 'F']);

        // Positive test
        $this->getJson("/api/webservice/users/{$user->id}/profile?requestID=REQ-USER-001")
            ->assertOk()
            ->assertJson([
                'status' => 'S',
                'requestID' => 'REQ-USER-001',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Syed Profile Test',
                    'role' => 'donor',
                    'phone' => '555-8888',
                ],
            ]);
    }

    public function test_entity_orm_relationships_match_analysis_class_diagram(): void
    {
        $donor = User::forceCreate([
            'name' => 'Diagram Donor',
            'email' => 'diagramdonor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $driver = User::forceCreate([
            'name' => 'Diagram Driver',
            'email' => 'diagramdriver@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'driver',
        ]);
        $category = Category::create(['name' => 'Diagram Category', 'description' => 'Test']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Diagram Produce',
            'category_id' => $category->id,
            'quantity' => 10,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'pickup_address' => 'Food Bank Distribution Center',
            'status' => 'available',
            'is_active' => true,
        ]);
        $item = DonationItem::create([
            'donation_id' => $donation->id,
            'item_name' => 'Carrots',
            'quantity' => 5,
            'unit' => 'boxes',
        ]);
        $request = FoodRequest::create([
            'user_id' => $donor->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 1,
            'request_date' => now()->toDateString(),
            'status' => 'reserved',
            'fulfillment_method' => 'home_delivery',
        ]);
        $reservation = Reservation::create([
            'request_id' => $request->id,
            'donation_id' => $donation->id,
            'quantity_reserved' => 1,
            'reservation_date' => now()->toDateString(),
        ]);
        $pickup = PickupSchedule::create([
            'donation_id' => $donation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => now()->addDay(),
            'status' => 'scheduled',
        ]);
        $deliverySchedule = DeliverySchedule::create([
            'reservation_id' => $reservation->id,
            'driver_id' => $driver->id,
            'scheduled_time' => now()->addDays(2),
            'status' => 'scheduled',
        ]);
        $delivery = Delivery::create([
            'reservation_id' => $reservation->id,
            'pickup_schedule_id' => $pickup->id,
            'delivery_schedule_id' => $deliverySchedule->id,
        ]);
        $locPickup = Location::create([
            'address' => 'Food Bank Central Hub',
            'type' => 'pickup',
            'reference_id' => $pickup->id,
        ]);
        $locDelivery = Location::create([
            'address' => 'Beneficiary Doorstep 42',
            'type' => 'delivery',
            'reference_id' => $deliverySchedule->id,
        ]);

        // Assert 1: Donation contains 1..* DonationItem
        $this->assertTrue($donation->donationItems->contains($item));
        $this->assertEquals($donation->id, $item->donation->id);

        // Assert 2: FoodRequest generates 0..1 Reservation
        $this->assertEquals($reservation->id, $request->reservation->id);
        $this->assertEquals($request->id, $reservation->foodRequest->id);

        // Assert 3: Reservation fulfilled by 1 Delivery
        $this->assertEquals($delivery->id, $reservation->delivery->id);
        $this->assertEquals($reservation->id, $delivery->reservation->id);

        // Assert 4: Delivery has 1 PickupSchedule & 1 DeliverySchedule
        $this->assertEquals($pickup->id, $delivery->pickupSchedule->id);
        $this->assertEquals($deliverySchedule->id, $delivery->deliverySchedule->id);

        // Assert 5: PickupSchedule pickup at 1 Location
        $this->assertEquals($locPickup->id, $pickup->location->id);
        $this->assertEquals($pickup->id, $locPickup->pickupSchedule->id);

        // Assert 6: DeliverySchedule deliver to 1 Location
        $this->assertEquals($locDelivery->id, $deliverySchedule->location->id);
        $this->assertEquals($deliverySchedule->id, $locDelivery->deliverySchedule->id);
    }

    public function test_all_modules_inter_communication_web_service_clients(): void
    {
        // 1. Module 1 consumes Module 3 (Available Donations)
        Http::fake([
            '*/webservice/donations/available*' => function ($request) {
                return Http::response([
                    'status' => 'S',
                    'requestID' => $request['requestID'] ?? 'REQ-TEST',
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'data' => [
                        'data' => [
                            ['id' => 1, 'title' => 'Simulated Bread', 'quantity' => 10],
                        ],
                    ],
                ], 200);
            },
        ]);

        $res1 = $this->getJson('/api/webservice/requests/available-donations');
        $res1->assertOk()->assertJson(['status' => 'S']);

        // 2. Module 2 consumes Module 1 (Approved Requests)
        Http::fake([
            '*/webservice/requests/approved*' => function ($request) {
                return Http::response([
                    'status' => 'S',
                    'requestID' => $request['requestID'] ?? 'REQ-TEST',
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'data' => [
                        ['id' => 1, 'quantity_requested' => 5, 'fulfillment_method' => 'home_delivery'],
                    ],
                ], 200);
            },
        ]);

        $res2 = $this->getJson('/api/webservice/scheduling/approved-requests');
        $res2->assertOk()->assertJson(['status' => 'S']);

        // 3. Module 3 consumes Module 4 (Donor User Profile)
        Http::fake([
            '*/webservice/users/*/profile*' => function ($request) {
                return Http::response([
                    'status' => 'S',
                    'requestID' => $request['requestID'] ?? 'REQ-TEST',
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'data' => [
                        'id' => 99,
                        'name' => 'Verified Donor',
                        'role' => 'donor',
                        'is_verified' => true,
                    ],
                ], 200);
            },
        ]);

        $res3 = $this->getJson('/api/webservice/donations/donor-profile?donor_id=99');
        $res3->assertOk()->assertJson(['status' => 'S', 'data' => ['name' => 'Verified Donor']]);

        // 4. Module 4 consumes Module 2 (Delivery Status)
        Http::fake([
            '*/webservice/deliveries/status*' => function ($request) {
                return Http::response([
                    'status' => 'S',
                    'requestID' => $request['requestID'] ?? 'REQ-TEST',
                    'timeStamp' => now()->format('Y-m-d H:i:s'),
                    'data' => [
                        'delivery_id' => 55,
                        'status' => 'in_transit',
                        'driver_name' => 'John Volunteer',
                    ],
                ], 200);
            },
        ]);

        $res4 = $this->getJson('/api/webservice/users/delivery-status?delivery_id=55');
        $res4->assertOk()->assertJson(['status' => 'S', 'data' => ['status' => 'in_transit']]);
    }

    public function test_web_service_client_handles_failures_gracefully(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Server Error'], 500),
        ]);

        $this->getJson('/api/webservice/requests/available-donations')
            ->assertStatus(502)
            ->assertJson(['status' => 'E']);

        $this->getJson('/api/webservice/scheduling/approved-requests')
            ->assertStatus(502)
            ->assertJson(['status' => 'E']);

        $this->getJson('/api/webservice/donations/donor-profile?donor_id=1')
            ->assertStatus(502)
            ->assertJson(['status' => 'E']);

        $this->getJson('/api/webservice/users/delivery-status?delivery_id=1')
            ->assertStatus(502)
            ->assertJson(['status' => 'E']);
    }

    public function test_donation_available_quantity_deducts_allocated_and_pending_requests(): void
    {
        $donor = User::forceCreate([
            'name' => 'Donation Donor',
            'email' => 'ddonor@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'donor',
        ]);
        $beneficiary1 = User::forceCreate([
            'name' => 'Beneficiary One',
            'email' => 'bene1@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $beneficiary2 = User::forceCreate([
            'name' => 'Beneficiary Two',
            'email' => 'bene2@example.test',
            'password' => Hash::make('StrongPassword!123'),
            'role' => 'beneficiary',
        ]);
        $category = Category::create(['name' => 'Qty Test Category', 'description' => 'Test']);
        $donation = Donation::create([
            'donor_id' => $donor->id,
            'title' => 'Fresh seasonal vegetable boxes',
            'category_id' => $category->id,
            'quantity' => 12,
            'unit' => 'boxes',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'pickup_address' => 'Food Bank Hub',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Initially: 12 available
        $this->assertEquals(12, $donation->availableQuantity());

        // Beneficiary 1 requests 4 boxes (status: pending)
        $service = app(FoodRequestService::class);
        $req1 = $service->create([
            'user_id' => $beneficiary1->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 4,
            'fulfillment_method' => 'food_bank_pickup',
        ]);

        // Now: 12 - 4 = 8 available!
        $this->assertEquals(8, $donation->fresh()->availableQuantity());

        // Beneficiary 2 requests 2 boxes
        $req2 = $service->create([
            'user_id' => $beneficiary2->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 2,
            'fulfillment_method' => 'food_bank_pickup',
        ]);

        // Now: 8 - 2 = 6 available!
        $this->assertEquals(6, $donation->fresh()->availableQuantity());

        // Attempting to request more than available (e.g. 7 boxes when only 6 left) throws exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only 6 boxes currently available');
        $service->create([
            'user_id' => $beneficiary1->id,
            'donation_id' => $donation->id,
            'quantity_requested' => 7,
            'fulfillment_method' => 'food_bank_pickup',
        ]);
    }
}
