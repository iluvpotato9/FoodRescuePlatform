<?php $__env->startSection('title', 'My Requests - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
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
            <form class="panel-body" method="POST" action="<?php echo e(route('requests.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="simple-form-step">
                    <span class="step-number">1</span>
                    <div class="form-group">
                        <label for="donation_id">Which food would you like?</label>
                        <select id="donation_id" name="donation_id" required>
                            <option value="">Choose available food</option>
                            <?php $__currentLoopData = $availableDonations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($donation->availableQuantity() > 0): ?>
                                    <option value="<?php echo e($donation->id); ?>" 
                                            data-max="<?php echo e((int) $donation->availableQuantity()); ?>"
                                            <?php if(old('donation_id', request('donation')) == $donation->id): echo 'selected'; endif; ?>>
                                        <?php echo e($donation->title); ?> — <?php echo e((int) $donation->availableQuantity()); ?> <?php echo e($donation->unit); ?> available
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity_requested">How much do you need?</label>
                        <input type="number" id="quantity_requested" name="quantity_requested" value="<?php echo e(old('quantity_requested', 1)); ?>" min="1" max="100000" step="1" inputmode="numeric" required>
                    </div>
                </div>
                <fieldset class="form-group">
                    <legend><span class="step-number">2</span> How would you like to receive it?</legend>
                    <div class="choice-grid">
                        <label class="choice-card">
                            <input type="radio" name="fulfillment_method" value="food_bank_pickup"
                                   <?php if(old('fulfillment_method', 'food_bank_pickup') === 'food_bank_pickup'): echo 'checked'; endif; ?>>
                            <span class="choice-title">I will collect it</span>
                            <span class="choice-description">
                                Collect from <strong><?php echo e(config('foodrescue.food_bank_name')); ?></strong><br>
                                <?php echo e(config('foodrescue.food_bank_address')); ?><br>
                                <?php echo e(config('foodrescue.food_bank_hours')); ?>

                            </span>
                        </label>
                        <label class="choice-card">
                            <input type="radio" name="fulfillment_method" value="home_delivery"
                                   <?php if(old('fulfillment_method') === 'home_delivery'): echo 'checked'; endif; ?>>
                            <span class="choice-title">Please deliver to my home</span>
                            <span class="choice-description">A volunteer driver will deliver after the food reaches the food bank.</span>
                        </label>
                    </div>
                </fieldset>
                <div class="form-group" id="delivery-address-group">
                    <label for="delivery_address">Where should we deliver the food?</label>
                    <textarea id="delivery_address" name="delivery_address" maxlength="500"
                              placeholder="Enter the full address, unit number and helpful directions."><?php echo e(old('delivery_address', auth()->user()->address)); ?></textarea>
                    <p class="field-hint">Only authorized administrators and the assigned driver can see this address.</p>
                </div>
                <div class="form-group">
                    <label for="notes">Anything else we should know? <span class="muted">(optional)</span></label>
                    <textarea id="notes" name="notes" maxlength="1000"
                              placeholder="Dietary restrictions or helpful information."><?php echo e(old('notes')); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit for admin review</button>
            </form>
        </div>

        <div class="card panel">
            <div class="panel-header"><h2>Request history</h2></div>
            <div class="panel-body request-step-list">
                <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $foodRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="request-step <?php echo e($updatedRequestIds->contains($foodRequest->id) ? 'request-has-update' : ''); ?>">
                        <div>
                            <p class="small muted">Request #<?php echo e($foodRequest->id); ?></p>
                            <h3><?php echo e($foodRequest->donation->title ?? 'Food request'); ?></h3>
                            <?php if($updatedRequestIds->contains($foodRequest->id)): ?><span class="badge badge-in_transit">New update</span><?php endif; ?>
                            <p><?php echo e($foodRequest->quantity_requested); ?> <?php echo e($foodRequest->donation->unit ?? ''); ?> · <?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'Home delivery' : 'Food bank collection'); ?></p>
                            <p class="small muted">Submitted <?php echo e($foodRequest->created_at->format('M j, Y')); ?></p>
                        </div>
                        <div>
                            <?php if($foodRequest->status === 'pending'): ?>
                                <span class="badge badge-pending">Waiting for admin review</span>
                            <?php elseif($foodRequest->status === 'approved'): ?>
                                <span class="badge badge-approved">Approved — waiting for donor pickup</span>
                            <?php elseif($foodRequest->status === 'reserved' && !$foodRequest->fulfillment_scheduled_at && $foodRequest->ready_at && $foodRequest->collection_deadline): ?>
                                <p><strong>Choose your <?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'delivery' : 'collection'); ?> time now.</strong></p>
                                <form method="POST" action="<?php echo e(route('requests.schedule', $foodRequest)); ?>" class="inline-actions">
                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                    <input type="datetime-local" name="fulfillment_scheduled_at"
                                           min="<?php echo e($foodRequest->ready_at->copy()->addDay()->startOfDay()->format('Y-m-d\TH:i')); ?>"
                                           max="<?php echo e($foodRequest->collection_deadline->format('Y-m-d\TH:i')); ?>" required>
                                    <button class="btn btn-primary" type="submit">Confirm time</button>
                                </form>
                                <p class="small muted">Available through <?php echo e($foodRequest->collection_deadline->format('M j, Y')); ?> during <?php echo e(config('foodrescue.food_bank_hours')); ?>.</p>
                            <?php elseif($foodRequest->fulfillment_scheduled_at): ?>
                                <span class="badge badge-reserved">Scheduled</span>
                                <p><strong><?php echo e($foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A')); ?></strong></p>
                                <p class="small muted"><?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'Admin will assign a driver.' : config('foodrescue.food_bank_address')); ?></p>
                            <?php else: ?>
                                <span class="badge badge-<?php echo e($foodRequest->status); ?>"><?php echo e(ucfirst($foodRequest->status)); ?></span>
                            <?php endif; ?>

                            <?php if(in_array($foodRequest->status, ['pending', 'approved'])): ?>
                                <form method="POST" action="<?php echo e(route('requests.status', $foodRequest)); ?>">
                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-secondary btn-sm" type="submit">Cancel request</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-state"><h3>No requests yet</h3><p class="muted">Use the simple form above to request food.</p></div>
                <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/requests/index.blade.php ENDPATH**/ ?>