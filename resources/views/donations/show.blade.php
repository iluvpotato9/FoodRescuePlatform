@extends('layouts.app')

@section('title', $donation->title . ' - Food Rescue Platform')

@section('content')
<section class="page-section">
    <div class="container">
        <div class="detail-grid">
            <div class="donation-image detail-image">
                @if($donation->image_path)
                    <img src="{{ Storage::url($donation->image_path) }}" alt="{{ $donation->title }}">
                @else
                    <img src="{{ asset('images/hero-rescue.svg') }}" alt="Community food donation illustration">
                @endif
            </div>
            <div>
                <div class="card-meta">
                    <span class="badge badge-{{ $donation->status }}">{{ str_replace('_', ' ', $donation->status) }}</span>
                    <span class="small muted">{{ $donation->category->name ?? 'General food' }}</span>
                </div>
                <h1 style="font-size: clamp(2.2rem, 5vw, 3.8rem)">{{ $donation->title }}</h1>
                <p class="lead">{{ $donation->description ?: 'This donor has listed surplus food for the community food bank.' }}</p>

                <div class="detail-list">
                    <div class="detail-item"><span>Quantity</span><strong>{{ $donation->quantity }} {{ $donation->unit }}</strong></div>
                    <div class="detail-item"><span>Expiry date</span><strong>{{ $donation->expiry_date->format('M j, Y') }}</strong></div>
                    <div class="detail-item"><span>Donor</span><strong>{{ $donation->donor->name ?? 'Community donor' }}</strong></div>
                    <div class="detail-item"><span>Availability</span><strong>{{ $donation->availableQuantity() }} {{ $donation->unit }} left</strong></div>
                    <div class="detail-item" style="grid-column: 1 / -1">
                        <span>Where the food is now</span>
                        <strong>
                            At {{ config('foodrescue.food_bank_name') }}
                        </strong>
                    </div>
                </div>

                <div class="card card-body">
                    <p class="eyebrow">Food bank location</p>
                    <h3>{{ config('foodrescue.food_bank_address') }}</h3>
                    <p class="muted small">{{ config('foodrescue.food_bank_hours') }}</p>
                </div>

                @auth
                    @if(auth()->user()->isBeneficiary() && $donation->isAvailable())
                        <div style="margin-top: 20px">
                            <a href="{{ route('requests.index', ['donation' => $donation->id]) }}" class="btn btn-primary">Request this food</a>
                        </div>
                    @elseif((auth()->id() === $donation->donor_id || auth()->user()->isAdmin()) && !$donation->reservations()->exists())
                        <form method="POST" action="{{ route('donations.destroy', $donation) }}" style="margin-top: 20px"
                              onsubmit="return confirm('Remove this donation? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Remove donation</button>
                        </form>
                    @endif
                @else
                    <div style="margin-top: 20px">
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in to request food</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
