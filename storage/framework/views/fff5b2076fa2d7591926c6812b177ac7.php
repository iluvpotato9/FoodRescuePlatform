<?php $__env->startSection('title', 'Create Donation - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
<section class="page-section">
    <div class="container">
        <div class="card form-card">
            <div class="form-header">
                <p class="eyebrow">New donation</p>
                <h1 style="font-size: clamp(2rem, 4vw, 3rem)">Share surplus food</h1>
                <p class="lead">Provide accurate quantity, expiry and pickup information so food can be matched safely.</p>
            </div>
            <form class="form-body" method="POST" action="<?php echo e(route('donations.store')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="title">Donation title</label>
                        <input id="title" name="title" value="<?php echo e(old('title')); ?>" minlength="3" maxlength="150"
                               placeholder="Example: Fresh vegetables and bread" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Food category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->id); ?>" <?php if(old('category_id') == $category->id): echo 'selected'; endif; ?>>
                                    <?php echo e($category->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Use-by or expiry date</label>
                        <input type="date" id="expiry_date" name="expiry_date" value="<?php echo e(old('expiry_date')); ?>"
                               min="<?php echo e(now()->addDay()->toDateString()); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo e(old('quantity')); ?>"
                               min="1" max="100000" step="1" inputmode="numeric" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select id="unit" name="unit" required>
                            <?php $__currentLoopData = ['kg' => 'Kilograms', 'g' => 'Grams', 'items' => 'Items', 'boxes' => 'Boxes', 'trays' => 'Trays', 'litres' => 'Litres']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('unit') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" maxlength="2000"
                                  placeholder="Describe the food, packaging, allergens and storage conditions."><?php echo e(old('description')); ?></textarea>
                    </div>
                    <div class="form-group full dropoff-notice">
                        <strong>Drop-off location</strong>
                        <p><?php echo e(config('foodrescue.food_bank_name')); ?><br><?php echo e(config('foodrescue.food_bank_address')); ?></p>
                        <p class="small muted">Please bring the food during <?php echo e(config('foodrescue.food_bank_hours')); ?>. Your private address is not requested.</p>
                    </div>
                    <div class="form-group">
                        <label for="image">Food photo <span class="muted">(optional)</span></label>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <p class="field-hint">JPG, PNG or WebP. Maximum 2 MB.</p>
                    </div>
                </div>
                <div class="inline-actions" style="margin-top: 26px">
                    <button type="submit" class="btn btn-primary">Publish donation</button>
                    <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/donations/create.blade.php ENDPATH**/ ?>