<?php $__env->startSection('title', 'Donations - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
<section class="page-section">
    <div class="container">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Community marketplace</p>
                <h1>Available food</h1>
                <p class="lead">Browse food currently available through the community food bank.</p>
            </div>
            <?php if(auth()->guard()->check()): ?>
                <?php if(auth()->user()->isDonor() || auth()->user()->isAdmin()): ?>
                    <a href="<?php echo e(route('donations.create')); ?>" class="btn btn-primary">List a donation</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <form class="card filter-bar category-filter" method="GET" action="<?php echo e(route('donations.index')); ?>">
            <div class="form-group">
                <label for="category_id">Food category</label>
                <select id="category_id" name="category_id">
                    <option value="">All categories</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category->id); ?>" <?php if(request('category_id') == $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter food</button>
            <?php if(request()->filled('category_id')): ?>
                <a href="<?php echo e(route('donations.index')); ?>" class="btn btn-secondary">Show all</a>
            <?php endif; ?>
        </form>

        <div class="grid">
            <?php $__empty_1 = true; $__currentLoopData = $donations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="card donation-card">
                    <div class="donation-image">
                        <?php if($donation->image_path): ?>
                            <img src="<?php echo e(Storage::url($donation->image_path)); ?>" alt="<?php echo e($donation->title); ?>">
                        <?php else: ?>
                            <svg class="donation-placeholder" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 3h10l1 4h3v2h-1l-1.1 11H5.1L4 9H3V7h3l1-4Zm2 2-.5 2h7L15 5H9Zm-2.9 4 .9 9h10l.9-9H6.1Z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-meta">
                            <span class="badge badge-<?php echo e($donation->status); ?>"><?php echo e(str_replace('_', ' ', $donation->status)); ?></span>
                            <span class="small muted"><?php echo e($donation->category->name ?? 'General food'); ?></span>
                        </div>
                        <h3><?php echo e($donation->title); ?></h3>
                        <p class="muted"><?php echo e(Str::limit($donation->description, 105)); ?></p>
                        <p class="small muted">Available through <?php echo e(config('foodrescue.food_bank_name')); ?></p>
                        <div class="card-footer">
                            <div>
                                <?php if((int) $donation->availableQuantity() < (int) $donation->quantity): ?>
                                    <strong><?php echo e((int) $donation->availableQuantity()); ?> of <?php echo e((int) $donation->quantity); ?> <?php echo e($donation->unit); ?> left</strong>
                                <?php else: ?>
                                    <strong><?php echo e((int) $donation->quantity); ?> <?php echo e($donation->unit); ?></strong>
                                <?php endif; ?>
                                <div class="small muted">Expires <?php echo e($donation->expiry_date->format('M j, Y')); ?></div>
                            </div>
                            <a href="<?php echo e(route('donations.show', $donation->id)); ?>" class="btn btn-secondary btn-sm">View details</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="card empty-state" style="grid-column: 1 / -1">
                    <h3>No food is available in this category</h3>
                    <p class="muted">Choose another category or return later.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="pagination"><?php echo e($donations->withQueryString()->links()); ?></div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/donations/index.blade.php ENDPATH**/ ?>