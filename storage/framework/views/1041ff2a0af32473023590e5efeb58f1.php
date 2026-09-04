<?php $__env->startSection('title', 'Dashboard - Food Rescue Platform'); ?>

<?php $__env->startSection('content'); ?>
<section>
    <div class="container">
        <header class="dashboard-header">
            <p class="eyebrow"><?php echo e(ucfirst($user->role)); ?> workspace</p>
            <h1 style="font-size: clamp(2.2rem, 4vw, 3.6rem)">Welcome, <?php echo e($user->name); ?></h1>
            <p class="lead">
                <?php if($user->isBeneficiary()): ?> Choose available food and follow one clear step at a time.
                <?php elseif($user->isDonor()): ?> Manage food listings and monitor their availability.
                <?php elseif($user->isDriver()): ?> Review assigned pickups and deliveries.
                <?php else: ?> Review requests and coordinate the food rescue network.
                <?php endif; ?>
            </p>
        </header>

        <?php if($notifications->count()): ?>
            <div class="card panel">
                <div class="panel-header">
                    <h2>Recent request updates</h2>
                    <?php if($notifications->whereNull('read_at')->count()): ?>
                        <form method="POST" action="<?php echo e(route('notifications.read')); ?>"><?php echo csrf_field(); ?><button class="btn btn-secondary btn-sm">Mark updates as read</button></form>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="padding: 10px 0; <?php echo e(!$loop->last ? 'border-bottom: 1px solid var(--line)' : ''); ?>">
                            <strong><?php echo e($notification->data['title'] ?? 'Status update'); ?></strong>
                            <div class="small muted"><?php echo e($notification->data['message'] ?? ''); ?> &middot; <?php echo e($notification->created_at->diffForHumans()); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if($user->isBeneficiary() && isset($requests)): ?>
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Waiting for admin</span><span class="stat-value"><?php echo e($requests->where('status', 'pending')->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Donor pickups being arranged</span><span class="stat-value"><?php echo e($requests->where('status', 'approved')->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Choose a time</span><span class="stat-value"><?php echo e($requests->where('status', 'reserved')->whereNotNull('ready_at')->whereNull('fulfillment_scheduled_at')->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Confirmed</span><span class="stat-value"><?php echo e($requests->whereNotNull('fulfillment_scheduled_at')->count()); ?></span></div>
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div><p class="eyebrow">Step 1</p><h2>Choose available food</h2></div>
                    <a href="<?php echo e(route('donations.index')); ?>" class="btn btn-secondary btn-sm">See all food</a>
                </div>
                <div class="panel-body simple-food-grid">
                    <?php $__empty_1 = true; $__currentLoopData = $availableDonations->filter(fn($d) => $d->availableQuantity() > 0); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="simple-food-card">
                            <div>
                                <h3><?php echo e($donation->title); ?></h3>
                                <p class="muted"><?php echo e((int) $donation->availableQuantity()); ?> <?php echo e($donation->unit); ?> available · expires <?php echo e($donation->expiry_date->format('M j')); ?></p>
                            </div>
                            <a class="btn btn-primary" href="<?php echo e(route('requests.index', ['donation' => $donation->id])); ?>">Choose this food</a>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="empty-state"><h3>No food is available right now</h3><p class="muted">Please check again later.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card panel">
                <div class="panel-header"><div><p class="eyebrow">Your next steps</p><h2>Your requests</h2></div></div>
                <div class="panel-body request-step-list">
                    <?php $__empty_1 = true; $__currentLoopData = $requests->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $foodRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="request-step <?php echo e($updatedRequestIds->contains($foodRequest->id) ? 'request-has-update' : ''); ?>">
                            <div>
                                <p class="small muted">Request #<?php echo e($foodRequest->id); ?></p>
                                <h3><?php echo e($foodRequest->donation->title ?? 'Food request'); ?></h3>
                                <?php if($updatedRequestIds->contains($foodRequest->id)): ?><span class="badge badge-in_transit">New update</span><?php endif; ?>
                                <p class="muted"><?php echo e($foodRequest->quantity_requested); ?> <?php echo e($foodRequest->donation->unit ?? ''); ?> · <?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'Home delivery' : 'Collect from food bank'); ?></p>
                            </div>
                            <div>
                                <?php if($foodRequest->status === 'pending'): ?>
                                    <span class="badge badge-pending">Waiting for admin review</span>
                                <?php elseif($foodRequest->status === 'approved'): ?>
                                    <span class="badge badge-approved">Approved — pickup being arranged</span>
                                <?php elseif($foodRequest->status === 'reserved' && !$foodRequest->fulfillment_scheduled_at && $foodRequest->ready_at && $foodRequest->collection_deadline): ?>
                                    <p><strong>Food is at the bank. Choose your <?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'delivery' : 'collection'); ?> time.</strong></p>
                                    <form method="POST" action="<?php echo e(route('requests.schedule', $foodRequest)); ?>" class="inline-actions">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <input
                                            type="datetime-local"
                                            name="fulfillment_scheduled_at"
                                            min="<?php echo e($foodRequest->ready_at->copy()->addDay()->startOfDay()->format('Y-m-d\TH:i')); ?>"
                                            max="<?php echo e($foodRequest->collection_deadline->format('Y-m-d\TH:i')); ?>"
                                            required
                                        >
                                        <button class="btn btn-primary" type="submit">Confirm time</button>
                                    </form>
                                    <p class="small muted">Choose between <?php echo e($foodRequest->ready_at->copy()->addDay()->format('M j')); ?> and <?php echo e($foodRequest->collection_deadline->format('M j, Y')); ?>, during <?php echo e(config('foodrescue.food_bank_hours')); ?>.</p>
                                <?php elseif($foodRequest->fulfillment_scheduled_at): ?>
                                    <span class="badge badge-reserved">Time confirmed</span>
                                    <p class="small"><strong><?php echo e($foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A')); ?></strong></p>
                                    <p class="small muted"><?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'Admin will assign an available driver.' : 'Please collect from '.config('foodrescue.food_bank_address').'.'); ?></p>
                                <?php else: ?>
                                    <span class="badge badge-<?php echo e($foodRequest->status); ?>"><?php echo e(ucfirst($foodRequest->status)); ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="empty-state"><h3>You have no requests yet</h3><p class="muted">Choose an available food item above to begin.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if($user->isDonor() && isset($donations)): ?>
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Total listings</span><span class="stat-value"><?php echo e($donations->total()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Available</span><span class="stat-value"><?php echo e($donations->where('status', 'available')->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Reserved</span><span class="stat-value"><?php echo e($donations->where('status', 'reserved')->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Delivered</span><span class="stat-value"><?php echo e($donations->where('status', 'delivered')->count()); ?></span></div>
            </div>
            <div class="card panel">
                <div class="panel-header">
                    <h2>Your food donations</h2>
                    <a href="<?php echo e(route('donations.create')); ?>" class="btn btn-primary btn-sm">List food</a>
                </div>
                <?php if($donations->count()): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Donation</th><th>Status</th><th>Update quantity and expiry</th><th>Details</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $donations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><a href="<?php echo e(route('donations.show', $donation)); ?>"><strong><?php echo e($donation->title); ?></strong></a><div class="small muted"><?php echo e($donation->category->name ?? ''); ?></div></td>
                                        <td><span class="badge badge-<?php echo e($donation->status); ?>"><?php echo e(str_replace('_', ' ', $donation->status)); ?></span></td>
                                        <td>
                                            <form method="POST" action="<?php echo e(route('donations.update', $donation)); ?>" class="compact-edit-form">
                                                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                <label>
                                                    <span class="small muted">Quantity (<?php echo e($donation->unit); ?>)</span>
                                                    <input type="number" name="quantity" value="<?php echo e($donation->quantity); ?>" min="1" max="100000" step="1" inputmode="numeric" required>
                                                </label>
                                                <label>
                                                    <span class="small muted">Expiry date</span>
                                                    <input type="date" name="expiry_date" value="<?php echo e($donation->expiry_date->format('Y-m-d')); ?>" min="<?php echo e(now()->addDay()->format('Y-m-d')); ?>" required>
                                                </label>
                                                <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
                                            </form>
                                        </td>
                                        <td><a href="<?php echo e(route('donations.show', $donation)); ?>" class="btn btn-secondary btn-sm">View</a></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No donations listed</h3><p class="muted">Publish safe surplus food for your local community.</p><a href="<?php echo e(route('donations.create')); ?>" class="btn btn-primary">Create first donation</a></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($user->isDriver() && isset($dashboard)): ?>
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Active pickups</span><span class="stat-value"><?php echo e($dashboard['pickups']->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Active deliveries</span><span class="stat-value"><?php echo e($dashboard['deliveries']->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Today's assignments</span><span class="stat-value"><?php echo e($dashboard['pickups']->whereBetween('scheduled_time', [now()->startOfDay(), now()->endOfDay()])->count() + $dashboard['deliveries']->whereBetween('scheduled_time', [now()->startOfDay(), now()->endOfDay()])->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Role</span><span class="stat-value" style="font-size: 1.25rem">Driver</span></div>
            </div>
            <div class="card panel">
                <div class="panel-header"><h2>Pickup assignments</h2></div>
                <?php if($dashboard['pickups']->count()): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Donation</th><th>Scheduled</th><th>Status</th><th>Update</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $dashboard['pickups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pickup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($pickup->donation->title ?? 'Donation unavailable'); ?></strong>
                                            <div class="small muted">Collect from: <?php echo e($pickup->donation->pickup_address ?? ''); ?></div>
                                            <div class="small muted">Bring to: <?php echo e(config('foodrescue.food_bank_address')); ?></div>
                                        </td>
                                        <td><?php echo e($pickup->scheduled_time->format('M j, g:i A')); ?></td>
                                        <td><span class="badge badge-<?php echo e($pickup->status); ?>"><?php echo e(str_replace('_', ' ', $pickup->status)); ?></span></td>
                                        <td>
                                            <?php if($pickup->scheduled_time->copy()->startOfDay()->lte(today())): ?>
                                                <form method="POST" action="<?php echo e(route('pickups.status', $pickup)); ?>" class="inline-actions">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                    <select name="status" aria-label="Pickup status">
                                                        <?php $__currentLoopData = ['scheduled', 'picked_up', 'in_transit', 'completed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($status); ?>" <?php if($pickup->status === $status): echo 'selected'; endif; ?>><?php echo e(ucwords(str_replace('_', ' ', $status))); ?></option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                    <button class="btn btn-primary btn-sm">Update</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge badge-pending">Updates open <?php echo e($pickup->scheduled_time->format('M j')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No active pickups</h3><p class="muted">New assignments will appear here.</p></div>
                <?php endif; ?>
            </div>
            <div class="card panel">
                <div class="panel-header"><div><h2>Home delivery assignments</h2><p class="small muted" style="margin: 4px 0 0">The destination below is the address chosen by the beneficiary.</p></div></div>
                <?php if($dashboard['deliveries']->count()): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary and food</th><th>Deliver to</th><th>Scheduled</th><th>Status update</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $dashboard['deliveries']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deliverySchedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($deliverySchedule->reservation->foodRequest->user->name ?? 'Beneficiary'); ?></strong>
                                            <div class="small muted"><?php echo e($deliverySchedule->reservation->donation->title); ?></div>
                                        </td>
                                        <td>
                                            <strong><?php echo e($deliverySchedule->reservation->foodRequest->delivery_address); ?></strong>
                                            <div class="small muted"><?php echo e($deliverySchedule->reservation->foodRequest->user->phone ?: 'No phone provided'); ?></div>
                                        </td>
                                        <td><?php echo e($deliverySchedule->scheduled_time->format('M j, g:i A')); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo e($deliverySchedule->status); ?>"><?php echo e(str_replace('_', ' ', $deliverySchedule->status)); ?></span>
                                            <?php if($deliverySchedule->delivery && $deliverySchedule->scheduled_time->copy()->startOfDay()->lte(today())): ?>
                                                <form method="POST" action="<?php echo e(route('deliveries.status', $deliverySchedule->delivery)); ?>" class="inline-actions" style="margin-top: 8px">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                    <select name="status" aria-label="Delivery status">
                                                        <?php $__currentLoopData = ['scheduled', 'picked_up', 'in_transit', 'delivered', 'completed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($status); ?>" <?php if($deliverySchedule->status === $status): echo 'selected'; endif; ?>><?php echo e(ucwords(str_replace('_', ' ', $status))); ?></option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                    <button class="btn btn-primary btn-sm">Update</button>
                                                </form>
                                            <?php elseif($deliverySchedule->delivery): ?>
                                                <p class="small muted"><strong>Status controls open on <?php echo e($deliverySchedule->scheduled_time->format('M j, Y')); ?>.</strong> This is an upcoming assignment notice.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No active deliveries</h3><p class="muted">Scheduled deliveries will appear here.</p></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($user->isAdmin()): ?>
            <div class="stat-grid">
                <div class="card stat-card"><span class="stat-label">Pending requests</span><span class="stat-value"><?php echo e($pendingRequests->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Active donations</span><span class="stat-value"><?php echo e($activeDonations); ?></span></div>
                <div class="card stat-card"><span class="stat-label">Volunteer drivers</span><span class="stat-value"><?php echo e($drivers->count()); ?></span></div>
                <div class="card stat-card"><span class="stat-label">System status</span><span class="stat-value" style="font-size: 1.25rem">Operational</span></div>
            </div>
            <div class="admin-flow-summary">
                <div class="card card-body">
                    <p class="eyebrow">Step 1</p>
                    <h3>Review requests</h3>
                    <p class="muted"><?php echo e($pendingRequests->count()); ?> waiting for approval or rejection.</p>
                </div>
                <div class="card card-body">
                    <p class="eyebrow">Step 2</p>
                    <h3>Beneficiary chooses a time</h3>
                    <p class="muted"><?php echo e($awaitingBeneficiaryTime->count()); ?> waiting to choose a time within three days.</p>
                </div>
                <div class="card card-body">
                    <p class="eyebrow">Step 3</p>
                    <h3>Assign home deliveries</h3>
                    <p class="muted"><?php echo e($unassignedReservations->count()); ?> chosen <?php echo e(Str::plural('delivery time', $unassignedReservations->count())); ?> need a driver.</p>
                </div>
            </div>
            <div class="card panel">
                <div class="panel-header"><h2>Requests awaiting review</h2></div>
                <?php if($pendingRequests->count()): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary</th><th>Submitted</th><th>Need summary</th><th>Decision</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $pendingRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $foodRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong><?php echo e($foodRequest->user->name); ?></strong><div class="small muted"><?php echo e($foodRequest->user->email); ?></div></td>
                                        <td><?php echo e($foodRequest->created_at->format('M j, Y')); ?></td>
                                        <td>
                                            <strong><?php echo e($foodRequest->donation->title ?? 'Donation unavailable'); ?></strong>
                                            <div><?php echo e($foodRequest->quantity_requested); ?> <?php echo e($foodRequest->donation->unit ?? ''); ?></div>
                                            <?php if($foodRequest->notes): ?><div class="small muted"><?php echo e(Str::limit($foodRequest->notes, 80)); ?></div><?php endif; ?>
                                            <div class="small muted" style="margin-top: 5px">
                                                <?php echo e($foodRequest->fulfillment_method === 'home_delivery' ? 'Needs home delivery' : 'Will collect from food bank'); ?>

                                            </div>
                                        </td>
                                        <td>
                                            <div class="inline-actions">
                                                <form method="POST" action="<?php echo e(route('requests.status', $foodRequest)); ?>">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-primary btn-sm">Approve</button>
                                                </form>
                                                <form method="POST" action="<?php echo e(route('requests.status', $foodRequest)); ?>">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>All requests reviewed</h3><p class="muted">There are no pending beneficiary requests.</p></div>
                <?php endif; ?>
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div>
                        <h2>Ready for collection at the food bank</h2>
                        <p class="small muted" style="margin: 4px 0 0"><?php echo e(config('foodrescue.food_bank_address')); ?></p>
                    </div>
                </div>
                <?php if($bankPickupReservations->count()): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Beneficiary</th><th>Food</th><th>Chosen collection time</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $bankPickupReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong><?php echo e($reservation->foodRequest->user->name); ?></strong></td>
                                        <td><?php echo e($reservation->donation->title); ?><div class="small muted"><?php echo e($reservation->quantity_reserved); ?> <?php echo e($reservation->donation->unit); ?></div></td>
                                        <td>
                                            <strong><?php echo e($reservation->foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A')); ?></strong>
                                            <div class="small muted"><?php echo e($reservation->foodRequest->user->phone ?: 'No phone provided'); ?></div>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?php echo e(route('requests.status', $reservation->foodRequest)); ?>">
                                                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="action" value="complete">
                                                <button class="btn btn-primary btn-sm" type="submit">Mark as collected</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No collections waiting</h3><p class="muted">Beneficiaries ready to collect will appear here.</p></div>
                <?php endif; ?>
            </div>

            <div class="card panel">
                <div class="panel-header">
                    <div><p class="eyebrow">Step 3</p><h2>Assign home delivery drivers</h2><p class="small muted">The beneficiary has already chosen the delivery time. Drivers with another nearby assignment cannot be selected.</p></div>
                </div>
                <div class="panel-body admin-action-list">
                    <?php $__empty_1 = true; $__currentLoopData = $unassignedReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="admin-action-card">
                            <div>
                                <h3><?php echo e($reservation->foodRequest->user->name); ?></h3>
                                <p><?php echo e($reservation->donation->title); ?> · <?php echo e($reservation->quantity_reserved); ?> <?php echo e($reservation->donation->unit); ?></p>
                                <p class="muted"><strong>Deliver to:</strong> <?php echo e($reservation->foodRequest->delivery_address); ?></p>
                                <p class="muted"><strong>Chosen time:</strong> <?php echo e($reservation->foodRequest->fulfillment_scheduled_at->format('M j, Y · g:i A')); ?></p>
                            </div>
                            <form method="POST" action="<?php echo e(route('deliveries.store')); ?>" class="action-form-grid">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="reservation_id" value="<?php echo e($reservation->id); ?>">
                                <label>Available driver
                                    <select name="driver_id" required>
                                        <option value="">Choose driver</option>
                                        <?php $__currentLoopData = $driverAvailabilityByRequest->get($reservation->request_id, collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($driver->id); ?>" <?php if(!$driver->is_available): echo 'disabled'; endif; ?>>
                                                <?php echo e($driver->name); ?><?php echo e($driver->is_available ? '' : ' — unavailable'); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </label>
                                <button class="btn btn-primary" type="submit">Confirm driver</button>
                            </form>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="empty-state"><h3>No home deliveries need a driver</h3><p class="muted">They appear here after a beneficiary chooses a delivery time.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\alzw7\Downloads\FoodRescuePlatformClean (2)\FoodRescuePlatformClean\resources\views/dashboard.blade.php ENDPATH**/ ?>