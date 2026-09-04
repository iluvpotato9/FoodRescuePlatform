<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Food Rescue Platform'); ?></title>
    <meta name="description" content="Connect surplus food with people who need it through safe local donation, reservation, pickup and delivery.">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
</head>
<body>
    <header class="site-header">
        <nav class="nav-shell" aria-label="Main navigation">
            <a class="brand" href="<?php echo e(route('home')); ?>">
                <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M12.2 21c-4.4 0-7.8-3.2-7.8-7.4 0-3.9 2.6-6.5 6.3-6.5.7 0 1.4.1 2 .4.6-.3 1.3-.4 2-.4 3.4 0 5.8 2.5 5.8 6 0 4.5-3.6 7.9-8.3 7.9ZM12 7c-.2-2.3 1-4 3.4-4.8.2 2.4-1 4-3.4 4.8Z"/>
                </svg>
                <span>FoodBridge</span>
            </a>
            <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-links">
                <a class="nav-link <?php echo e(request()->routeIs('home') ? 'active' : ''); ?>" href="<?php echo e(route('home')); ?>">Home</a>
                <a class="nav-link <?php echo e(request()->routeIs('donations.index', 'donations.show') ? 'active' : ''); ?>" href="<?php echo e(route('donations.index')); ?>">Find food</a>
            <?php if(auth()->guard()->check()): ?>
                <a class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
                <?php if(auth()->user()->isBeneficiary()): ?>
                    <a class="nav-link <?php echo e(request()->routeIs('requests.*') ? 'active' : ''); ?>" href="<?php echo e(route('requests.index')); ?>">My requests</a>
                    <a class="nav-link <?php echo e(request()->routeIs('profile.*') ? 'active' : ''); ?>" href="<?php echo e(route('profile.show')); ?>">My profile</a>
                <?php endif; ?>
                <?php if(auth()->user()->isDonor()): ?>
                    <a class="nav-link <?php echo e(request()->routeIs('donations.create') ? 'active' : ''); ?>" href="<?php echo e(route('donations.create')); ?>">Donate food</a>
                <?php endif; ?>
                <div class="nav-user">
                    <span class="avatar" aria-hidden="true"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 2))); ?></span>
                    <form action="<?php echo e(route('logout')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-secondary btn-sm">Sign out</button>
                    </form>
                </div>
            <?php else: ?>
                <a class="nav-link" href="<?php echo e(route('login')); ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?php echo e(route('register')); ?>">Create account</a>
            <?php endif; ?>
            </div>
        </nav>
    </header>

    <?php if(session('success')): ?>
        <div class="alert alert-success" role="status"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <main class="page">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a class="brand" href="<?php echo e(route('home')); ?>">
                        <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.2 21c-4.4 0-7.8-3.2-7.8-7.4 0-3.9 2.6-6.5 6.3-6.5.7 0 1.4.1 2 .4.6-.3 1.3-.4 2-.4 3.4 0 5.8 2.5 5.8 6 0 4.5-3.6 7.9-8.3 7.9ZM12 7c-.2-2.3 1-4 3.4-4.8.2 2.4-1 4-3.4 4.8Z"/></svg>
                        FoodBridge
                    </a>
                    <p>Helping communities move safe surplus food from local donors to the people who need it most.</p>
                </div>
                <div>
                    <h3>Platform</h3>
                    <div class="footer-links">
                        <a href="<?php echo e(route('donations.index')); ?>">Available donations</a>
                        <?php if(auth()->guard()->guest()): ?> <a href="<?php echo e(route('register')); ?>">Join the network</a> <?php endif; ?>
                        <?php if(auth()->guard()->check()): ?> <a href="<?php echo e(route('dashboard')); ?>">Your dashboard</a> <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h3>Our purpose</h3>
                    <p>Supporting UN Sustainable Development Goal 2: Zero Hunger.</p>
                </div>
            </div>
            <div class="footer-bottom">&copy; <?php echo e(date('Y')); ?> FoodBridge. Community food rescue platform.</div>
        </div>
    </footer>
    <script>
        const toggle = document.querySelector('.nav-toggle');
        const links = document.querySelector('.nav-links');
        toggle?.addEventListener('click', () => {
            const open = links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(open));
        });
    </script>
</body>
</html>
<?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/layouts/app.blade.php ENDPATH**/ ?>