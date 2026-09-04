@extends('layouts.app')

@section('title', 'Login - Food Rescue Platform')

@section('content')
<div class="auth-shell">
    <section class="auth-panel">
        <div class="auth-form">
            <p class="eyebrow">Welcome back</p>
            <h1 style="font-size: clamp(2rem, 4vw, 3rem)">Sign in to FoodBridge</h1>
            <p class="lead">Access your donations, requests and scheduled deliveries.</p>

            <form method="POST" action="{{ route('login') }}" style="margin-top: 30px">
                @csrf
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           autocomplete="email" maxlength="254" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" maxlength="128" required>
                </div>
                <label class="check-row" style="margin-bottom: 20px">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in on this device</span>
                </label>
                <button type="submit" class="btn btn-primary btn-block">Sign in securely</button>
            </form>
            <p class="muted small" style="margin-top: 22px; text-align: center">
                New to FoodBridge? <a href="{{ route('register') }}"><strong>Create an account</strong></a>
            </p>
        </div>
    </section>
    <aside class="auth-aside">
        <div class="auth-aside-content">
            <p class="eyebrow" style="color: #f1c77e">Community powered</p>
            <h2>Every successful handoff keeps good food in the community.</h2>
            <p>Track each request from approval through reservation, pickup and delivery.</p>
        </div>
    </aside>
</div>
@endsection
