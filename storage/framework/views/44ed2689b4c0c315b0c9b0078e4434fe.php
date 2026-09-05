<?php $__env->startSection('title', 'Register - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-shell">
    <section class="auth-panel">
        <div class="auth-form" style="max-width: 580px">
            <p class="eyebrow">Join the network</p>
            <h1 style="font-size: clamp(2rem, 4vw, 3rem)">Create your account</h1>
            <p class="lead">Choose the role that best describes how you will use FoodBridge.</p>

            <form method="POST" action="<?php echo e(route('register')); ?>" style="margin-top: 28px">
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name')); ?>"
                               autocomplete="name" minlength="2" maxlength="100" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="role">Account type</label>
                        <select id="role" name="role" required>
                            <option value="beneficiary" <?php if(old('role') === 'beneficiary'): echo 'selected'; endif; ?>>Beneficiary</option>
                            <option value="donor" <?php if(old('role') === 'donor'): echo 'selected'; endif; ?>>Food donor</option>
                            <option value="driver" <?php if(old('role') === 'driver'): echo 'selected'; endif; ?>>Volunteer driver</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
                               autocomplete="email" maxlength="254" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" minlength="15" maxlength="128" required>
                        <p class="field-hint">Use at least 15 characters. A long, unique passphrase is easiest to remember and harder to guess.</p>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password" minlength="15" maxlength="128" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone <span class="muted">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo e(old('phone')); ?>"
                               autocomplete="tel" maxlength="30">
                    </div>
                    <div class="form-group">
                        <label for="address">Address <span class="muted">(optional)</span></label>
                        <input type="text" id="address" name="address" value="<?php echo e(old('address')); ?>"
                               autocomplete="street-address" maxlength="500">
                    </div>
                    <div id="beneficiary-fields" class="form-group full">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="household_size">Household size</label>
                                <input type="number" id="household_size" name="household_size"
                                       value="<?php echo e(old('household_size', 1)); ?>" min="1" max="30">
                            </div>
                            <div class="form-group">
                                <label for="income_level">Income level</label>
                                <select id="income_level" name="income_level">
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                    <option value="low">Low income</option>
                                    <option value="moderate">Moderate income</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label for="dietary_needs">Dietary needs <span class="muted">(optional)</span></label>
                                <textarea id="dietary_needs" name="dietary_needs" maxlength="500"><?php echo e(old('dietary_needs')); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label class="check-row">
                            <input type="checkbox" name="terms" value="1" required>
                            <span>I confirm that the information provided is accurate and agree to use the platform responsibly.</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 22px">Create secure account</button>
            </form>
            <p class="muted small" style="margin-top: 22px; text-align: center">
                Already registered? <a href="<?php echo e(route('login')); ?>"><strong>Sign in</strong></a>
            </p>
        </div>
    </section>
    <aside class="auth-aside">
        <div class="auth-aside-content">
            <p class="eyebrow" style="color: #f1c77e">Built for trust</p>
            <h2>Your role gives you the right tools and protects community data.</h2>
            <p>Beneficiaries request food, donors publish surplus, and drivers coordinate safe handoffs.</p>
        </div>
    </aside>
</div>
<script>
function toggleBeneficiaryFields() {
    const role = document.getElementById('role').value;
    document.getElementById('beneficiary-fields').style.display = role === 'beneficiary' ? 'block' : 'none';
    document.getElementById('household_size').required = role === 'beneficiary';
}
document.getElementById('role').addEventListener('change', toggleBeneficiaryFields);
toggleBeneficiaryFields();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/auth/register.blade.php ENDPATH**/ ?>