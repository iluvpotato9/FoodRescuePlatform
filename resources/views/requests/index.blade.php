@extends('layouts.app')

@section('title', 'My Requests - Food Rescue Platform')

@section('content')
<section class="page-section">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Food assistance</p>
                <h1>My requests</h1>
                <p class="lead">Tell us what you need and choose the easiest way to receive your food.</p>
            </div>
        </div>

        <div class="card panel">
            <div class="panel-header"><div><p class="eyebrow">Two simple choices</p><h2>Request available food</h2></div></div>
            <form class="panel-body" method="POST" action="{{ route('requests.store') }}">
                @csrf
                <div class="simple-form-step">
                    <span class="step-number">1</span>
                    <div class="form-group">
                        <label for="donation_id">Which food would you like?</label>
                        <select id="donation_id" name="donation_id" required>
                            <option value="">Choose available food</option>
                            @foreach($availableDonations as $donation)
                                @if($donation->availableQuantity() > 0)
                                    <option value="{{ $donation->id }}" 
                                            data-max="{{ (int) $donation->availableQuantity() }}"
                                            @selected(old('donation_id', request('donation')) == $donation->id)>
                                        {{ $donation->title }} — {{ (int) $donation->availableQuantity() }} {{ $donation->unit }} available
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity_requested">How much do you need?</label>
                        <input type="number" id="quantity_requested" name="quantity_requested" value="{{ old('quantity_requested', 1) }}" min="1" max="100000" step="1" inputmode="numeric" required>
                    </div>
                </div>
                <fieldset class="form-group">
                    <legend><span class="step-number">2</span> How would you like to receive it?</legend>
                    <div class="choice-grid">
                        <label class="choice-card">
                            <input type="radio" name="fulfillment_method" value="food_bank_pickup"
                                   @checked(old('fulfillment_method', 'food_bank_pickup') === 'food_bank_pickup')>
                            <span class="choice-title">I will collect it</span>
                            <span class="choice-description">
                                Collect from <strong>{{ config('foodrescue.food_bank_name') }}</strong><br>
                                {{ config('foodrescue.food_bank_address') }}<br>
                                {{ config('foodrescue.food_bank_hours') }}
                            </span>
                        </label>
                        <label class="choice-card">
                            <input type="radio" name="fulfillment_method" value="home_delivery"
                                   @checked(old('fulfillment_method') === 'home_delivery')>
                            <span class="choice-title">Please deliver to my home</span>
                            <span class="choice-description">A volunteer driver will deliver after the food reaches the food bank.</span>
                        </label>
                    </div>
                </fieldset>
                <div class="form-group" id="delivery-address-group">
                    <label for="delivery_address">Where should we deliver the food?</label>
                    <textarea id="delivery_address" name="delivery_address" maxlength="500"
                              placeholder="Enter the full address, unit number and helpful directions.">{{ old('delivery_address', auth()->user()->address) }}</textarea>
                    <p class="field-hint">Only authorized administrators and the assigned driver can see this address.</p>
                </div>
                <div class="form-group">
                    <label for="notes">Anything else we should know? <span class="muted">(optional)</span></label>
                    <textarea id="notes" name="notes" maxlength="1000"
                              placeholder="Dietary restrictions or helpful information.">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit for admin review</button>
            </form>
        </div>

        <div class="card panel">
            <div class="panel-header"><h2>Request history</h2></div>
            <div class="panel-body request-step-list">
                @forelse($requests as $foodRequest)
                    <article class="request-step {{ $updatedRequestIds->contains($foodRequest->id) ? 'request-has-update' : '' }}">
                        <div>
                            <p class="small muted">Request #{{ $foodRequest->id }}</p>
                            <h3>{{ $foodRequest->donation->title ?? 'Food request' }}</h3>
                            @if($updatedRequestIds->contains($foodRequest->id))<span class="badge badge-in_transit">New update</span>@endif
                            <p>{{ $foodRequest->quantity_requested }} {{ $foodRequest->donation->unit ?? '' }} · {{ $foodRequest->fulfillment_method === 'home_delivery' ? 'Home delivery' : 'Food bank collection' }}</p>
                            <p class="small muted">Submitted {{ $foodRequest->created_at->format('M j, Y') }}</p>
                        </div>
                        <div>
                            @if($foodRequest->status === 'pending')
                                <span class="badge badge-pending">Waiting for admin review</span>
                            @elseif($foodRequest->status === 'approved')
                                <span class="badge badge-approved">Approved — waiting for donor pickup</span>
                            @elseif($foodRequest->status === 'reserved' && !$foodRequest->fulfillment_scheduled_at && $foodRequest->ready_at && $foodRequest->collection_deadline)
                                <p><strong>Choose your {{ $foodRequest->fulfillment_method === 'home_delivery' ? 'delivery' : 'collection' }} time now.</strong></p>
                                <form method="POST" action="{{ route('requests.schedule', $foodRequest) }}" class="inline-actions">
                                    @csrf @method('PATCH')
                                    <input type="datetime-local" name="fulfillment_scheduled_at"
                                           min="{{ $foodRequest->ready_at->copy()->addDay()->startOfDay()->format('Y-m-d\TH:i') }}"
                                           max="{{ $foodRequest->collection_deadline->format('Y-m-d\TH:i') }}" required>
                                    <button class="btn btn-primary" type="submit">Confirm time</button>
                                </form>
                                <p class="small muted">Available through {{ $foodRequest->collection_deadline->format('M j, Y') }} during {{ config('foodrescue.food_bank_hours') }}.</p>
                            @elseif($foodRequest->fulfillment_scheduled_at)
                                <span class="badge badge-reserved">Scheduled</span>
                                <p><strong>{{ $foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A') }}</strong></p>
                                <p class="small muted">{{ $foodRequest->fulfillment_method === 'home_delivery' ? 'Admin will assign a driver.' : config('foodrescue.food_bank_address') }}</p>
                            @else
                                <span class="badge badge-{{ $foodRequest->status }}">{{ ucfirst($foodRequest->status) }}</span>
                            @endif

                            @if(in_array($foodRequest->status, ['pending', 'approved']))
                                <form method="POST" action="{{ route('requests.status', $foodRequest) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-secondary btn-sm" type="submit">Cancel request</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><h3>No requests yet</h3><p class="muted">Use the simple form above to request food.</p></div>
                @endforelse
            </div>
        </div>
    </div>
</section>
<script>
    const fulfillmentChoices = document.querySelectorAll('input[name="fulfillment_method"]');
    const addressGroup = document.getElementById('delivery-address-group');
    const addressInput = document.getElementById('delivery_address');

    function updateFulfillmentFields() {
        const selected = document.querySelector('input[name="fulfillment_method"]:checked')?.value;
        const needsDelivery = selected === 'home_delivery';
        addressGroup.hidden = !needsDelivery;
        addressInput.required = needsDelivery;
    }

    fulfillmentChoices.forEach(choice => choice.addEventListener('change', updateFulfillmentFields));
    updateFulfillmentFields();

    const donationSelect = document.getElementById('donation_id');
    const quantityInput = document.getElementById('quantity_requested');

    function syncAvailableQuantity() {
        const selectedOption = donationSelect.options[donationSelect.selectedIndex];
        const maxQty = selectedOption?.getAttribute('data-max');
        if (maxQty) {
            quantityInput.max = maxQty;
            if (parseInt(quantityInput.value) > parseInt(maxQty)) {
                quantityInput.value = maxQty;
            }
        } else {
            quantityInput.removeAttribute('max');
        }
    }

    donationSelect?.addEventListener('change', syncAvailableQuantity);
    syncAvailableQuantity();
</script>
@endsection
