{{-- 
Module: Food Donation Management Module
Author: Liang Yun Ci (Student ID: 2408076)
Course: BMIT3173 Integrative Programming
--}}

@extends('layouts.app')

@section('title', 'Donations - Food Rescue Platform')

@section('content')
<section class="page-section">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Community marketplace</p>
                <h1>Available food</h1>
                <p class="lead">Browse food currently available through the community food bank.</p>
            </div>
            @auth
                @if(auth()->user()->isDonor() || auth()->user()->isAdmin())
                    <a href="{{ route('donations.create') }}" class="btn btn-primary">List a donation</a>
                @endif
            @endauth
        </div>

        <form class="card filter-bar category-filter" method="GET" action="{{ route('donations.index') }}">
            <div class="form-group">
                <label for="category_id">Food category</label>
                <select id="category_id" name="category_id">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter food</button>
            @if(request()->filled('category_id'))
                <a href="{{ route('donations.index') }}" class="btn btn-secondary">Show all</a>
            @endif
        </form>

        <div class="grid">
            @forelse($donations as $donation)
                <article class="card donation-card">
                    <div class="donation-image">
                        @if($donation->image_path)
                            <img src="{{ Storage::url($donation->image_path) }}" alt="{{ $donation->title }}">
                        @else
                            <svg class="donation-placeholder" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 3h10l1 4h3v2h-1l-1.1 11H5.1L4 9H3V7h3l1-4Zm2 2-.5 2h7L15 5H9Zm-2.9 4 .9 9h10l.9-9H6.1Z"/></svg>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="card-meta">
                            <span class="badge badge-{{ $donation->status }}">{{ str_replace('_', ' ', $donation->status) }}</span>
                            <span class="small muted">{{ $donation->category->name ?? 'General food' }}</span>
                        </div>
                        <h3>{{ $donation->title }}</h3>
                        <p class="muted">{{ Str::limit($donation->description, 105) }}</p>
                        <p class="small muted">Available through {{ config('foodrescue.food_bank_name') }}</p>
                        <div class="card-footer">
                            <div>
                                @if((int) $donation->availableQuantity() < (int) $donation->quantity)
                                    <strong>{{ (int) $donation->availableQuantity() }} of {{ (int) $donation->quantity }} {{ $donation->unit }} left</strong>
                                @else
                                    <strong>{{ (int) $donation->quantity }} {{ $donation->unit }}</strong>
                                @endif
                                <div class="small muted">Expires {{ $donation->expiry_date->format('M j, Y') }}</div>
                            </div>
                            <a href="{{ route('donations.show', $donation->id) }}" class="btn btn-secondary btn-sm">View details</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card empty-state" style="grid-column: 1 / -1">
                    <h3>No food is available in this category</h3>
                    <p class="muted">Choose another category or return later.</p>
                </div>
            @endforelse
        </div>

        <div class="pagination">{{ $donations->withQueryString()->links() }}</div>
    </div>
</section>
@endsection
