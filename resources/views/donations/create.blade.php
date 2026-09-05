{{-- 
Module: Food Donation Management Module
Author: Liang Yun Ci (Student ID: 2408076)
Course: BMIT3173 Integrative Programming
--}}

@extends('layouts.app')

@section('title', 'Create Donation - Food Rescue Platform')

@section('content')
<section class="page-section">
    <div class="container">
        <div class="card form-card">
            <div class="form-header">
                <p class="eyebrow">New donation</p>
                <h1 style="font-size: clamp(2rem, 4vw, 3rem)">Share surplus food</h1>
                <p class="lead">Provide accurate quantity, expiry and pickup information so food can be matched safely.</p>
            </div>
            <form class="form-body" method="POST" action="{{ route('donations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="title">Donation title</label>
                        <input id="title" name="title" value="{{ old('title') }}" minlength="3" maxlength="150"
                               placeholder="Example: Fresh vegetables and bread" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Food category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Use-by or expiry date</label>
                        <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}"
                               min="{{ now()->addDay()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}"
                               min="1" max="100000" step="1" inputmode="numeric" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select id="unit" name="unit" required>
                            @foreach(['kg' => 'Kilograms', 'g' => 'Grams', 'items' => 'Items', 'boxes' => 'Boxes', 'trays' => 'Trays', 'litres' => 'Litres'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('unit') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" maxlength="2000"
                                  placeholder="Describe the food, packaging, allergens and storage conditions.">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group full dropoff-notice">
                        <strong>Drop-off location</strong>
                        <p>{{ config('foodrescue.food_bank_name') }}<br>{{ config('foodrescue.food_bank_address') }}</p>
                        <p class="small muted">Please bring the food during {{ config('foodrescue.food_bank_hours') }}. Your private address is not requested.</p>
                    </div>
                    <div class="form-group">
                        <label for="image">Food photo <span class="muted">(optional)</span></label>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <p class="field-hint">JPG, PNG or WebP. Maximum 2 MB.</p>
                    </div>
                </div>
                <div class="inline-actions" style="margin-top: 26px">
                    <button type="submit" class="btn btn-primary">Publish donation</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
