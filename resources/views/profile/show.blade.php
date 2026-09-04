@extends('layouts.app')

@section('title', 'My Profile - Food Rescue Platform')

@section('content')
<section class="page-section">
    <div class="container profile-container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Private account information</p>
                <h1>My profile</h1>
                <p class="lead">Keep your contact, delivery, household, and dietary information up to date.</p>
            </div>
        </div>

        <div class="card panel">
            <div class="panel-header"><h2>Personal information</h2></div>
            <form class="panel-body" method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label for="household_size">People in household</label>
                        <input type="number" id="household_size" name="household_size" value="{{ old('household_size', $user->beneficiaryProfile?->household_size ?? 1) }}" min="1" max="30" required>
                    </div>
                    <div class="form-group full">
                        <label for="address">Home delivery address</label>
                        <textarea id="address" name="address" maxlength="500" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
                    </div>
                    <div class="form-group full">
                        <label for="dietary_needs">Dietary needs or allergies</label>
                        <textarea id="dietary_needs" name="dietary_needs" maxlength="1000">{{ old('dietary_needs', $user->beneficiaryProfile?->dietary_needs) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="income_level">Income or support information <span class="muted">(optional)</span></label>
                        <input id="income_level" name="income_level" value="{{ old('income_level', $user->beneficiaryProfile?->income_level) }}" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact">Emergency contact <span class="muted">(optional)</span></label>
                        <input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $user->beneficiaryProfile?->emergency_contact) }}" maxlength="255">
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save profile</button>
            </form>
        </div>

        <div class="card panel danger-zone">
            <div class="panel-header"><h2>Delete account</h2></div>
            <form class="panel-body" method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Permanently delete your account and all related data? This cannot be undone.')">
                @csrf @method('DELETE')
                <p>This permanently removes your profile, requests, reservations, notifications, sessions, and account from the database.</p>
                <div class="form-group">
                    <label for="delete-password">Enter your password to confirm</label>
                    <input type="password" id="delete-password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-danger" type="submit">Permanently delete my account</button>
            </form>
        </div>
    </div>
</section>
@endsection
