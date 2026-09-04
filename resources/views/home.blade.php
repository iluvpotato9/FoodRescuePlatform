@extends('layouts.app')

@section('title', 'Home - Food Rescue Platform')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Local food, shared with purpose</p>
            <h1>Good food belongs on a table, not in a landfill.</h1>
            <p class="lead">FoodBridge connects verified local donors, community members and volunteer drivers so surplus food reaches people safely and quickly.</p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary">Join FoodBridge</a>
                    <a href="{{ route('donations.index') }}" class="btn btn-secondary">Browse available food</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Open your dashboard</a>
                    <a href="{{ route('donations.index') }}" class="btn btn-secondary">View donations</a>
                @endguest
            </div>
            <div class="trust-row">
                <span class="trust-item">Role-based accounts</span>
                <span class="trust-item">Tracked reservations</span>
                <span class="trust-item">Coordinated delivery</span>
            </div>
        </div>
        <div class="hero-visual">
            <img src="{{ asset('images/hero-rescue.svg') }}" alt="Illustration of fresh food prepared for community collection">
        </div>
    </div>
</section>

<section class="impact-strip">
    <div class="container">
        <div class="impact-grid">
            <div class="impact-item">
                <span class="impact-value">One network</span>
                <span class="impact-label">Donors, beneficiaries and drivers working together</span>
            </div>
            <div class="impact-item">
                <span class="impact-value">Real-time</span>
                <span class="impact-label">Reservation, pickup and delivery status tracking</span>
            </div>
            <div class="impact-item">
                <span class="impact-value">Local first</span>
                <span class="impact-label">Food matched with needs in the community</span>
            </div>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Available now</p>
                <h2>Food ready to be rescued</h2>
                <p class="lead">Current donations are ordered by the nearest expiry date to reduce waste.</p>
            </div>
            <a href="{{ route('donations.index') }}" class="btn btn-secondary">View all donations</a>
        </div>

        <div class="grid">
            @forelse($donations as $donation)
                <article class="card donation-card">
                    <div class="donation-image">
                        @if($donation->image_path)
                            <img src="{{ Storage::url($donation->image_path) }}" alt="{{ $donation->title }}">
                        @else
                            <svg class="donation-placeholder" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M7 3h10l1 4h3v2h-1l-1.1 11H5.1L4 9H3V7h3l1-4Zm2 2-.5 2h7L15 5H9Zm-2.9 4 .9 9h10l.9-9H6.1Z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="card-meta">
                            <span class="badge badge-{{ $donation->status }}">{{ str_replace('_', ' ', $donation->status) }}</span>
                            <span class="small muted">{{ $donation->category->name ?? 'General food' }}</span>
                        </div>
                        <h3>{{ $donation->title }}</h3>
                        <p class="muted">{{ Str::limit($donation->description, 95) }}</p>
                        <div class="card-footer">
                            <div>
                                <strong>{{ $donation->quantity }} {{ $donation->unit }}</strong>
                                <div class="small muted">Expires {{ $donation->expiry_date->format('M j') }}</div>
                            </div>
                            <a href="{{ route('donations.show', $donation->id) }}" class="btn btn-secondary btn-sm">View details</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card empty-state" style="grid-column: 1 / -1">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 3h10l1 4h3v2h-1l-1.1 11H5.1L4 9H3V7h3l1-4Zm2 2-.5 2h7L15 5H9Zm-2.9 4 .9 9h10l.9-9H6.1Z"/></svg>
                    <h3>No donations are available right now</h3>
                    <p class="muted">New food donations will appear here as soon as donors publish them.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="page-section" style="background: var(--sage-50)">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">How it works</p>
                <h2>A clear path from surplus to support</h2>
            </div>
        </div>
        <div class="grid">
            <div class="card card-body"><p class="eyebrow">01 &mdash; Donate</p><h3>List safe surplus food</h3><p class="muted">Donors publish quantity, expiry details and a pickup location.</p></div>
            <div class="card card-body"><p class="eyebrow">02 &mdash; Request</p><h3>Choose food and collection method</h3><p class="muted">Beneficiaries request a quantity and choose food bank collection or home delivery.</p></div>
            <div class="card card-body"><p class="eyebrow">03 &mdash; Review and rescue</p><h3>Bring approved food to the bank</h3><p class="muted">Admin approves the request and assigns a driver to collect food from the donor.</p></div>
            <div class="card card-body"><p class="eyebrow">04 &mdash; Receive</p><h3>Choose a time within three days</h3><p class="muted">The beneficiary chooses a collection or delivery time after the food reaches the bank.</p></div>
        </div>
    </div>
</section>
@endsection
