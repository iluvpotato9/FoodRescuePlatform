<?php

/**
 * System: Food Rescue and Community Food Bank Platform (SDG 2: Zero Hunger)
 * Module: Core Web Application Integration (MVC Presentation Layer)
 * Authors (Group 3):
 *  - Loo Zi Wei (2408082) - Food Request and Reservation Module
 *  - Loo Zhi Yin (2410857) - Pickup and Delivery Scheduling
 *  - Liang Yun Ci (2408076) - Food Donation Management Module
 *  - Syed Raiz (2410921) - User and Authentication Module
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Donation;
use App\Models\FoodRequest as FoodRequestModel;
use App\Models\PickupSchedule;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Donation\DonationService;
use App\Services\FoodRequest\FoodRequestService;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class WebController extends Controller
{
    public function __construct(
        private DonationService $donationService,
        private FoodRequestService $foodRequestService,
        private SchedulingService $schedulingService,
    ) {}

    public function home(): View
    {
        $donations = $this->donationService->listAvailable(['per_page' => 6]);

        return view('home', compact('donations'));
    }

    public function dashboard(): View
    {
        $user = Auth::user();
        $data = [
            'user' => $user,
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
        ];

        if ($user->isBeneficiary()) {
            $data['requests'] = $this->foodRequestService->listForUser($user->id);
            $data['availableDonations'] = $this->donationService->listAvailable(['per_page' => 6]);
            $data['updatedRequestIds'] = $user->unreadNotifications()
                ->get()
                ->pluck('data.food_request_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique();
        } elseif ($user->isDonor()) {
            $data['donations'] = $this->donationService->list(['donor_id' => $user->id, 'per_page' => 10]);
        } elseif ($user->isDriver()) {
            $data['dashboard'] = $this->schedulingService->driverDashboard($user->id);
        } elseif ($user->isAdmin()) {
            $data['pendingRequests'] = FoodRequestModel::with(['user', 'donation'])
                ->where('status', 'pending')
                ->latest()
                ->get();
            $data['activeDonations'] = Donation::where('is_active', true)->count();
            $data['drivers'] = User::where('role', 'driver')->orderBy('name')->get();
            $data['unassignedReservations'] = Reservation::with(['donation', 'foodRequest.user'])
                ->whereDoesntHave('delivery')
                ->whereHas('donation', fn ($query) => $query->where('collection_location', 'food_bank'))
                ->whereHas('foodRequest', fn ($query) => $query
                    ->where('status', 'reserved')
                    ->whereNotNull('fulfillment_scheduled_at')
                    ->where('fulfillment_method', 'home_delivery'))
                ->latest()
                ->get();
            $data['bankPickupReservations'] = Reservation::with(['donation', 'foodRequest.user'])
                ->whereHas('donation', fn ($query) => $query->where('collection_location', 'food_bank'))
                ->whereHas('foodRequest', fn ($query) => $query
                    ->where('status', 'reserved')
                    ->whereNotNull('fulfillment_scheduled_at')
                    ->where('fulfillment_method', 'food_bank_pickup'))
                ->latest()
                ->get();
            $data['awaitingBeneficiaryTime'] = FoodRequestModel::with(['user', 'donation'])
                ->where('status', 'reserved')
                ->whereNull('fulfillment_scheduled_at')
                ->get();
            $data['driverAvailabilityByRequest'] = $data['unassignedReservations']->mapWithKeys(
                fn (Reservation $reservation) => [
                    $reservation->request_id => $this->schedulingService
                        ->driversWithAvailability($reservation->foodRequest->fulfillment_scheduled_at),
                ]
            );
        }

        return view('dashboard', $data);
    }

    public function donations(Request $request): View
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $donations = $this->donationService->listAvailable($filters);
        $categories = Category::orderBy('name')->get();

        return view('donations.index', compact('donations', 'categories'));
    }

    public function donationShow(int $id): View
    {
        $donation = $this->donationService->find($id);

        return view('donations.show', compact('donation'));
    }

    public function donationCreateForm(): View
    {
        $categories = Category::all();

        return view('donations.create', compact('categories'));
    }

    public function donationStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:categories,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit' => ['required', 'in:kg,g,items,boxes,trays,litres'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $validated['donor_id'] = Auth::id();
        $validated['status'] = 'available';
        $validated['is_active'] = true;
        $validated['pickup_address'] = config('foodrescue.food_bank_address');
        $validated['pickup_time'] = null;
        $validated['collection_location'] = 'food_bank';

        $donation = $this->donationService->create($validated, $request->file('image'));

        return redirect()->route('donations.show', $donation)
            ->with('success', 'Your donation is now available to the community.');
    }

    public function donationDestroy(Donation $donation): RedirectResponse
    {
        $this->authorizeDonationOwner($donation);

        if ($donation->reservations()->exists()) {
            return back()->withErrors(['donation' => 'A donation with reservations cannot be deleted.']);
        }

        $this->donationService->delete($donation);

        return redirect()->route('dashboard')->with('success', 'Donation removed.');
    }

    public function donationUpdate(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorizeDonationOwner($donation);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'expiry_date' => ['required', 'date', 'after:today'],
        ]);

        $reservedQuantity = (float) $donation->reservations()->sum('quantity_reserved');
        if ((float) $validated['quantity'] < $reservedQuantity) {
            return back()->withErrors([
                'quantity' => "Quantity cannot be lower than the {$reservedQuantity} already approved.",
            ]);
        }

        $this->donationService->update($donation, $validated);

        return back()->with('success', 'Donation quantity and expiry date updated.');
    }

    public function requests(): View
    {
        $requests = $this->foodRequestService->listForUser(Auth::id());
        $availableDonations = $this->donationService->listAvailable(['per_page' => 20]);
        $updatedRequestIds = Auth::user()->unreadNotifications()
            ->get()
            ->pluck('data.food_request_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        return view('requests.index', compact('requests', 'availableDonations', 'updatedRequestIds'));
    }

    public function notificationsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Request updates marked as read.');
    }

    public function requestStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'donation_id' => ['required', 'exists:donations,id'],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_method' => ['required', 'in:food_bank_pickup,home_delivery'],
            'delivery_address' => [
                Rule::requiredIf($request->input('fulfillment_method') === 'home_delivery'),
                'nullable',
                'string',
                'max:500',
            ],
        ]);
        $validated['user_id'] = Auth::id();

        try {
            $this->foodRequestService->create($validated);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['donation_id' => $exception->getMessage()]);
        }

        return redirect()->route('requests.index')
            ->with('success', 'Your request was submitted for review.');
    }

    public function requestStatus(Request $request, FoodRequestModel $foodRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,complete,cancel'],
        ]);
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort_unless($foodRequest->user_id === $user->id && $validated['action'] === 'cancel', 403);
        }

        try {
            if ($validated['action'] === 'approve') {
                $this->foodRequestService->approveRequest($foodRequest);
            } else {
                $this->foodRequestService->updateStatus($foodRequest, $validated['action']);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Request status updated.');
    }

    public function requestSchedule(Request $request, FoodRequestModel $foodRequest): RedirectResponse
    {
        abort_unless($foodRequest->user_id === Auth::id(), 403);
        $validated = $request->validate([
            'fulfillment_scheduled_at' => ['required', 'date'],
        ]);

        try {
            $this->foodRequestService->scheduleFulfillment(
                $foodRequest,
                $validated['fulfillment_scheduled_at']
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['fulfillment_scheduled_at' => $exception->getMessage()]);
        }

        $message = $foodRequest->fulfillment_method === 'home_delivery'
            ? 'Your preferred delivery time was sent to the admin for driver assignment.'
            : 'Your food bank collection time is confirmed.';

        return back()->with('success', $message);
    }

    public function reservationStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'exists:food_requests,id'],
            'donation_id' => ['required', 'exists:donations,id'],
            'quantity_reserved' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $validated['user_id'] = Auth::id();

        try {
            $this->foodRequestService->createReservation($validated);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['reservation' => $exception->getMessage()]);
        }

        return back()->with('success', 'Food reservation confirmed.');
    }

    public function reservationDestroy(Reservation $reservation): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $reservation->foodRequest->user_id === $user->id, 403);

        $this->foodRequestService->deleteReservation($reservation);

        return back()->with('success', 'Reservation cancelled.');
    }

    public function pickupStatus(Request $request, PickupSchedule $pickup): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,picked_up,in_transit,delivered,completed,cancelled'],
        ]);
        $user = Auth::user();
        abort_unless($user->isAdmin() || $pickup->driver_id === $user->id, 403);
        if ($user->isDriver() && $pickup->scheduled_time->copy()->startOfDay()->gt(today())) {
            return back()->withErrors([
                'status' => 'You can update this pickup on '.$pickup->scheduled_time->format('M j, Y').'.',
            ]);
        }

        $this->schedulingService->updatePickupStatus($pickup, $validated['status']);

        return back()->with('success', 'Pickup status updated and participants notified.');
    }

    public function pickupStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'donation_id' => ['required', 'exists:donations,id'],
            'driver_id' => ['required', Rule::exists('users', 'id')->where('role', 'driver')],
            'scheduled_time' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->schedulingService->createPickup($validated);

        return back()->with('success', 'Pickup assigned to the volunteer driver.');
    }

    public function deliveryStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'exists:reservations,id'],
            'pickup_schedule_id' => ['nullable', 'exists:pickup_schedules,id'],
            'driver_id' => ['required', Rule::exists('users', 'id')->where('role', 'driver')],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->schedulingService->createDelivery($validated);

        return back()->with('success', 'Delivery scheduled successfully.');
    }

    public function deliveryStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,picked_up,in_transit,delivered,completed,cancelled'],
        ]);
        $user = Auth::user();
        abort_unless($user->isAdmin() || $delivery->deliverySchedule?->driver_id === $user->id, 403);
        if ($user->isDriver() && $delivery->deliverySchedule->scheduled_time->copy()->startOfDay()->gt(today())) {
            return back()->withErrors([
                'status' => 'You can update this delivery on '.$delivery->deliverySchedule->scheduled_time->format('M j, Y').'.',
            ]);
        }

        $this->schedulingService->updateDeliveryStatus($delivery, $validated['status']);

        return back()->with('success', 'Delivery status updated and participants notified.');
    }

    private function authorizeDonationOwner(Donation $donation): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $donation->donor_id === $user->id, 403);
    }
}
