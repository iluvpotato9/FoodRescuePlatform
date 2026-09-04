<?php $__env->startSection('title', $donation->title . ' - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
<section class="page-section">
    <div class="container">
        <div class="detail-grid">
            <div class="donation-image detail-image">
                <?php if($donation->image_path): ?>
                    <img src="<?php echo e(Storage::url($donation->image_path)); ?>" alt="<?php echo e($donation->title); ?>">
                <?php else: ?>
                    <img src="<?php echo e(asset('images/hero-rescue.svg')); ?>" alt="Community food donation illustration">
                <?php endif; ?>
            </div>
            <div>
                <div class="card-meta">
                    <span class="badge badge-<?php echo e($donation->status); ?>"><?php echo e(str_replace('_', ' ', $donation->status)); ?></span>
                    <span class="small muted"><?php echo e($donation->category->name ?? 'General food'); ?></span>
                </div>
                <h1 style="font-size: clamp(2.2rem, 5vw, 3.8rem)"><?php echo e($donation->title); ?></h1>
                <p class="lead"><?php echo e($donation->description ?: 'This donor has listed surplus food for the community food bank.'); ?></p>

                <div class="detail-list">
                    <div class="detail-item"><span>Quantity</span><strong><?php echo e($donation->quantity); ?> <?php echo e($donation->unit); ?></strong></div>
                    <div class="detail-item"><span>Expiry date</span><strong><?php echo e($donation->expiry_date->format('M j, Y')); ?></strong></div>
                    <div class="detail-item"><span>Donor</span><strong><?php echo e($donation->donor->name ?? 'Community donor'); ?></strong></div>
                    <div class="detail-item"><span>Availability</span><strong><?php echo e($donation->availableQuantity()); ?> <?php echo e($donation->unit); ?> left</strong></div>
                    <div class="detail-item" style="grid-column: 1 / -1">
                        <span>Where the food is now</span>
                        <strong>
                            At <?php echo e(config('foodrescue.food_bank_name')); ?>

                        </strong>
                    </div>
                </div>

                <div class="card card-body">
                    <p class="eyebrow">Food bank location</p>
                    <h3><?php echo e(config('foodrescue.food_bank_address')); ?></h3>
                    <p class="muted small"><?php echo e(config('foodrescue.food_bank_hours')); ?></p>
                </div>

                <?php if(auth()->guard()->check()): ?>
                    <?php if(auth()->user()->isBeneficiary() && $donation->isAvailable()): ?>
                        <div style="margin-top: 20px">
                            <a href="<?php echo e(route('requests.index', ['donation' => $donation->id])); ?>" class="btn btn-primary">Request this food</a>
                        </div>
                    <?php elseif((auth()->id() === $donation->donor_id || auth()->user()->isAdmin()) && !$donation->reservations()->exists()): ?>
                        <form method="POST" action="<?php echo e(route('donations.destroy', $donation)); ?>" style="margin-top: 20px"
                              onsubmit="return confirm('Remove this donation? This action cannot be undone.')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-danger" type="submit">Remove donation</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="margin-top: 20px">
                        <a href="<?php echo e(route('login')); ?>" class="btn btn-primary">Sign in to request food</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/donations/show.blade.php ENDPATH**/ ?>