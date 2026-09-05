{{-- 
System: Food Rescue and Community Food Bank Platform
File: Shared User Dashboard
Authors (Group 3):
- Loo Zi Wei (2408082)
- Loo Zhi Yin (2410857)
- Liang Yun Ci (2408076)
- Syed Raiz (2410921)
Course: BMIT3173 Integrative Programming
--}}

@extends('layouts.app')

@section('title', 'Dashboard - Food Rescue Platform')

@section('content')
<section>
    <div class="container">
        <header class="dashboard-header">
            <p class="eyebrow">{{ ucfirst($user->role) }} workspace</p>
            <h1 style="font-size: clamp(2.2rem, 4vw, 3.6rem)">Welcome, {{ $user->name }}</h1>
            <p class="lead">
                @if($user->isBeneficiary()) Choose available food and follow one clear step at a time.
                @elseif($user->isDonor()) Manage food listings and monitor their availability.
                @elseif($user->isDriver()) Review assigned pickups and deliveries.
                @else Review requests and coordinate the food rescue network.
                @endif
            </p>
        </header>

        @if($notifications->count())
            <div class="card panel">
                <div class="panel-header">
                    <h2>Recent request updates</h2>
                    @if($notifications->whereNull('read_at')->count())
                        <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-secondary btn-sm">Mark updates as read</button></form>
                    @endif
                </div>
                <div class="panel-body">
                    @foreach($notifications as $notification)
                        <div style="padding: 10px 0; {{ !$loop->last ? 'border-bottom: 1px solid var(--line)' : '' }}">
                            <strong>{{ $notification->data['title'] ?? 'Status update' }}</strong>
                            <div class="small muted">{{ $notification->data['message'] ?? '' }} &middot; {{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($user->isBeneficiary() && isset($requests))
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Waiting for admin</span><span class="stat-value">{{ $requests->where('status', 'pending')->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Donor pickups being arranged</span><span class="stat-value">{{ $requests->where('status', 'approved')->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Choose a time</span><span class="stat-value">{{ $requests->where('status', 'reserved')->whereNotNull('ready_at')->whereNull('fulfillment_scheduled_at')->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Confirmed</span><span class="stat-value">{{ $requests->whereNotNull('fulfillment_scheduled_at')->count() }}</span></div>
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div><p class="eyebrow">Step 1</p><h2>Choose available food</h2></div>
                    <a href="{{ route('donations.index') }}" class="btn btn-secondary btn-sm">See all food</a>
                </div>
                <div class="panel-body simple-food-grid">
                    @forelse($availableDonations->filter(fn($d) => $d->availableQuantity() > 0) as $donation)
                        <article class="simple-food-card">
                            <div>
                                <h3>{{ $donation->title }}</h3>
                                <p class="muted">{{ (int) $donation->availableQuantity() }} {{ $donation->unit }} available · expires {{ $donation->expiry_date->format('M j') }}</p>
                            </div>
                            <a class="btn btn-primary" href="{{ route('requests.index', ['donation' => $donation->id]) }}">Choose this food</a>
                        </article>
                    @empty
                        <div class="empty-state"><h3>No food is available right now</h3><p class="muted">Please check again later.</p></div>
                    @endforelse
                </div>
            </div>

            <div class="card panel">
                <div class="panel-header"><div><p class="eyebrow">Your next steps</p><h2>Your requests</h2></div></div>
                <div class="panel-body request-step-list">
                    @forelse($requests->take(6) as $foodRequest)
                        <article class="request-step {{ $updatedRequestIds->contains($foodRequest->id) ? 'request-has-update' : '' }}">
                            <div>
                                <p class="small muted">Request #{{ $foodRequest->id }}</p>
                                <h3>{{ $foodRequest->donation->title ?? 'Food request' }}</h3>
                                @if($updatedRequestIds->contains($foodRequest->id))<span class="badge badge-in_transit">New update</span>@endif
                                <p class="muted">{{ $foodRequest->quantity_requested }} {{ $foodRequest->donation->unit ?? '' }} · {{ $foodRequest->fulfillment_method === 'home_delivery' ? 'Home delivery' : 'Collect from food bank' }}</p>
                            </div>
                            <div>
                                @if($foodRequest->status === 'pending')
                                    <span class="badge badge-pending">Waiting for admin review</span>
                                @elseif($foodRequest->status === 'approved')
                                    <span class="badge badge-approved">Approved — pickup being arranged</span>
                                @elseif($foodRequest->status === 'reserved' && !$foodRequest->fulfillment_scheduled_at && $foodRequest->ready_at && $foodRequest->collection_deadline)
                                    <p><strong>Food is at the bank. Choose your {{ $foodRequest->fulfillment_method === 'home_delivery' ? 'delivery' : 'collection' }} time.</strong></p>
                                    <form method="POST" action="{{ route('requests.schedule', $foodRequest) }}" class="inline-actions">
                                        @csrf @method('PATCH')
                                        <input
                                            type="datetime-local"
                                            name="fulfillment_scheduled_at"
                                            min="{{ $foodRequest->ready_at->copy()->addDay()->startOfDay()->format('Y-m-d\TH:i') }}"
                                            max="{{ $foodRequest->collection_deadline->format('Y-m-d\TH:i') }}"
                                            required
                                        >
                                        <button class="btn btn-primary" type="submit">Confirm time</button>
                                    </form>
                                    <p class="small muted">Choose between {{ $foodRequest->ready_at->copy()->addDay()->format('M j') }} and {{ $foodRequest->collection_deadline->format('M j, Y') }}, during {{ config('foodrescue.food_bank_hours') }}.</p>
                                @elseif($foodRequest->fulfillment_scheduled_at)
                                    <span class="badge badge-reserved">Time confirmed</span>
                                    <p class="small"><strong>{{ $foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A') }}</strong></p>
                                    <p class="small muted">{{ $foodRequest->fulfillment_method === 'home_delivery' ? 'Admin will assign an available driver.' : 'Please collect from '.config('foodrescue.food_bank_address').'.' }}</p>
                                @else
                                    <span class="badge badge-{{ $foodRequest->status }}">{{ ucfirst($foodRequest->status) }}</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="empty-state"><h3>You have no requests yet</h3><p class="muted">Choose an available food item above to begin.</p></div>
                    @endforelse
                </div>
            </div>
        @endif

        @if($user->isDonor() && isset($donations))
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Total listings</span><span class="stat-value">{{ $donations->total() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Available</span><span class="stat-value">{{ $donations->where('status', 'available')->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Reserved</span><span class="stat-value">{{ $donations->where('status', 'reserved')->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Delivered</span><span class="stat-value">{{ $donations->where('status', 'delivered')->count() }}</span></div>
            </div>
            <div class="card panel">
                <div class="panel-header">
                    <h2>Your food donations</h2>
                    <a href="{{ route('donations.create') }}" class="btn btn-primary btn-sm">List food</a>
                </div>
                @if($donations->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Donation</th><th>Status</th><th>Update quantity and expiry</th><th>Details</th></tr></thead>
                            <tbody>
                                @foreach($donations as $donation)
                                    <tr>
                                        <td><a href="{{ route('donations.show', $donation) }}"><strong>{{ $donation->title }}</strong></a><div class="small muted">{{ $donation->category->name ?? '' }}</div></td>
                                        <td><span class="badge badge-{{ $donation->status }}">{{ str_replace('_', ' ', $donation->status) }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('donations.update', $donation) }}" class="compact-edit-form">
                                                @csrf @method('PATCH')
                                                <label>
                                                    <span class="small muted">Quantity ({{ $donation->unit }})</span>
                                                    <input type="number" name="quantity" value="{{ $donation->quantity }}" min="1" max="100000" step="1" inputmode="numeric" required>
                                                </label>
                                                <label>
                                                    <span class="small muted">Expiry date</span>
                                                    <input type="date" name="expiry_date" value="{{ $donation->expiry_date->format('Y-m-d') }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                                                </label>
                                                <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
                                            </form>
                                        </td>
                                        <td><a href="{{ route('donations.show', $donation) }}" class="btn btn-secondary btn-sm">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state"><h3>No donations listed</h3><p class="muted">Publish safe surplus food for your local community.</p><a href="{{ route('donations.create') }}" class="btn btn-primary">Create first donation</a></div>
                @endif
            </div>
        @endif

        @if($user->isDriver() && isset($dashboard))
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Active pickups</span><span class="stat-value">{{ $dashboard['pickups']->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Active deliveries</span><span class="stat-value">{{ $dashboard['deliveries']->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Today's assignments</span><span class="stat-value">{{ $dashboard['pickups']->whereBetween('scheduled_time', [now()->startOfDay(), now()->endOfDay()])->count() + $dashboard['deliveries']->whereBetween('scheduled_time', [now()->startOfDay(), now()->endOfDay()])->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Role</span><span class="stat-value" style="font-size: 1.25rem">Driver</span></div>
            </div>
            <div class="card panel">
                <div class="panel-header"><h2>Pickup assignments</h2></div>
                @if($dashboard['pickups']->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Donation</th><th>Scheduled</th><th>Status</th><th>Update</th></tr></thead>
                            <tbody>
                                @foreach($dashboard['pickups'] as $pickup)
                                    <tr>
                                        <td>
                                            <strong>{{ $pickup->donation->title ?? 'Donation unavailable' }}</strong>
                                            <div class="small muted">Collect from: {{ $pickup->donation->pickup_address ?? '' }}</div>
                                            <div class="small muted">Bring to: {{ config('foodrescue.food_bank_address') }}</div>
                                        </td>
                                        <td>{{ $pickup->scheduled_time->format('M j, g:i A') }}</td>
                                        <td><span class="badge badge-{{ $pickup->status }}">{{ str_replace('_', ' ', $pickup->status) }}</span></td>
                                        <td>
                                            @if($pickup->scheduled_time->copy()->startOfDay()->lte(today()))
                                                <form method="POST" action="{{ route('pickups.status', $pickup) }}" class="inline-actions">
                                                    @csrf @method('PATCH')
                                                    <select name="status" aria-label="Pickup status">
                                                        @foreach(['scheduled', 'picked_up', 'in_transit', 'completed'] as $status)
                                                            <option value="{{ $status }}" @selected($pickup->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-primary btn-sm">Update</button>
                                                </form>
                                            @else
                                                <span class="badge badge-pending">Updates open {{ $pickup->scheduled_time->format('M j') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state"><h3>No active pickups</h3><p class="muted">New assignments will appear here.</p></div>
                @endif
            </div>
            <div class="card panel">
                <div class="panel-header"><div><h2>Home delivery assignments</h2><p class="small muted" style="margin: 4px 0 0">The destination below is the address chosen by the beneficiary.</p></div></div>
                @if($dashboard['deliveries']->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary and food</th><th>Deliver to</th><th>Scheduled</th><th>Status update</th></tr></thead>
                            <tbody>
                                @foreach($dashboard['deliveries'] as $deliverySchedule)
                                    <tr>
                                        <td>
                                            <strong>{{ $deliverySchedule->reservation->foodRequest->user->name ?? 'Beneficiary' }}</strong>
                                            <div class="small muted">{{ $deliverySchedule->reservation->donation->title }}</div>
                                        </td>
                                        <td>
                                            <strong>{{ $deliverySchedule->reservation->foodRequest->delivery_address }}</strong>
                                            <div class="small muted">{{ $deliverySchedule->reservation->foodRequest->user->phone ?: 'No phone provided' }}</div>
                                        </td>
                                        <td>{{ $deliverySchedule->scheduled_time->format('M j, g:i A') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $deliverySchedule->status }}">{{ str_replace('_', ' ', $deliverySchedule->status) }}</span>
                                            @if($deliverySchedule->delivery && $deliverySchedule->scheduled_time->copy()->startOfDay()->lte(today()))
                                                <form method="POST" action="{{ route('deliveries.status', $deliverySchedule->delivery) }}" class="inline-actions" style="margin-top: 8px">
                                                    @csrf @method('PATCH')
                                                    <select name="status" aria-label="Delivery status">
                                                        @foreach(['scheduled', 'picked_up', 'in_transit', 'delivered', 'completed'] as $status)
                                                            <option value="{{ $status }}" @selected($deliverySchedule->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-primary btn-sm">Update</button>
                                                </form>
                                            @elseif($deliverySchedule->delivery)
                                                <p class="small muted"><strong>Status controls open on {{ $deliverySchedule->scheduled_time->format('M j, Y') }}.</strong> This is an upcoming assignment notice.</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state"><h3>No active deliveries</h3><p class="muted">Scheduled deliveries will appear here.</p></div>
                @endif
            </div>
        @endif

        @if($user->isAdmin())
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Pending requests</span><span class="stat-value">{{ $pendingRequests->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">Active donations</span><span class="stat-value">{{ $activeDonations }}</span></div>
                <div class="card stat-card"><span class="stat-label">Volunteer drivers</span><span class="stat-value">{{ $drivers->count() }}</span></div>
                <div class="card stat-card"><span class="stat-label">System status</span><span class="stat-value" style="font-size: 1.25rem">Operational</span></div>
            </div>
            <div class="admin-flow-summary">
                <div class="card card-body">
                    <p class="eyebrow">Step 1</p>
                    <h3>Review requests</h3>
                    <p class="muted">{{ $pendingRequests->count() }} waiting for approval or rejection.</p>
                </div>
                <div class="card card-body">
                    <p class="eyebrow">Step 2</p>
                    <h3>Beneficiary chooses a time</h3>
                    <p class="muted">{{ $awaitingBeneficiaryTime->count() }} waiting to choose a time within three days.</p>
                </div>
                <div class="card card-body">
                    <p class="eyebrow">Step 3</p>
                    <h3>Assign home deliveries</h3>
                    <p class="muted">{{ $unassignedReservations->count() }} chosen {{ Str::plural('delivery time', $unassignedReservations->count()) }} need a driver.</p>
                </div>
            </div>
            <div class="card panel">
                <div class="panel-header"><h2>Requests awaiting review</h2></div>
                @if($pendingRequests->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary</th><th>Submitted</th><th>Need summary</th><th>Decision</th></tr></thead>
                            <tbody>
                                @foreach($pendingRequests as $foodRequest)
                                    <tr>
                                        <td><strong>{{ $foodRequest->user->name }}</strong><div class="small muted">{{ $foodRequest->user->email }}</div></td>
                                        <td>{{ $foodRequest->created_at->format('M j, Y') }}</td>
                                        <td>
                                            <strong>{{ $foodRequest->donation->title ?? 'Donation unavailable' }}</strong>
                                            <div>{{ $foodRequest->quantity_requested }} {{ $foodRequest->donation->unit ?? '' }}</div>
                                            @if($foodRequest->notes)<div class="small muted">{{ Str::limit($foodRequest->notes, 80) }}</div>@endif
                                            <div class="small muted" style="margin-top: 5px">
                                                {{ $foodRequest->fulfillment_method === 'home_delivery' ? 'Needs home delivery' : 'Will collect from food bank' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="inline-actions">
                                                <form method="POST" action="{{ route('requests.status', $foodRequest) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-primary btn-sm">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('requests.status', $foodRequest) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state"><h3>All requests reviewed</h3><p class="muted">There are no pending beneficiary requests.</p></div>
                @endif
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div>
                        <h2>Ready for collection at the food bank</h2>
                        <p class="small muted" style="margin: 4px 0 0">{{ config('foodrescue.food_bank_address') }}</p>
                    </div>
                </div>
                @if($bankPickupReservations->count())
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary</th><th>Food</th><th>Chosen collection time</th><th>Action</th></tr></thead>
                            <tbody>
                                @foreach($bankPickupReservations as $reservation)
                                    <tr>
                                        <td><strong>{{ $reservation->foodRequest->user->name }}</strong></td>
                                        <td>{{ $reservation->donation->title }}<div class="small muted">{{ $reservation->quantity_reserved }} {{ $reservation->donation->unit }}</div></td>
                                        <td>
                                            <strong>{{ $reservation->foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A') }}</strong>
                                            <div class="small muted">{{ $reservation->foodRequest->user->phone ?: 'No phone provided' }}</div>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('requests.status', $reservation->foodRequest) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="complete">
                                                <button class="btn btn-primary btn-sm" type="submit">Mark as collected</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state"><h3>No collections waiting</h3><p class="muted">Beneficiaries ready to collect will appear here.</p></div>
                @endif
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div><p class="eyebrow">Step 3</p><h2>Assign home delivery drivers</h2><p class="small muted">The beneficiary has already chosen the delivery time. Drivers with another nearby assignment cannot be selected.</p></div>
                </div>
                <div class="panel-body admin-action-list">
                    @forelse($unassignedReservations as $reservation)
                        <article class="admin-action-card">
                            <div>
                                <h3>{{ $reservation->foodRequest->user->name }}</h3>
                                <p>{{ $reservation->donation->title }} · {{ $reservation->quantity_reserved }} {{ $reservation->donation->unit }}</p>
                                <p class="muted"><strong>Deliver to:</strong> {{ $reservation->foodRequest->delivery_address }}</p>
                                <p class="muted"><strong>Chosen time:</strong> {{ $reservation->foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A') }}</p>
                            </div>
                            <form method="POST" action="{{ route('deliveries.store') }}" class="action-form-grid">
                                @csrf
                                <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                                <label>Available driver
                                    <select name="driver_id" required>
                                        <option value="">Choose driver</option>
                                        @foreach($driverAvailabilityByRequest->get($reservation->request_id, collect()) as $driver)
                                            <option value="{{ $driver->id }}" @disabled(!$driver->is_available)>
                                                {{ $driver->name }}{{ $driver->is_available ? '' : ' — unavailable' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <button class="btn btn-primary" type="submit">Confirm driver</button>
                            </form>
                        </article>
                    @empty
                        <div class="empty-state"><h3>No home deliveries need a driver</h3><p class="muted">They appear here after a beneficiary chooses a delivery time.</p></div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
