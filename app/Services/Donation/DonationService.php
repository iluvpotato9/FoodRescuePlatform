<?php

/**
 * Module: Food Donation Management Module
 * Author: Liang Yun Ci (Student ID: 2408076)
 * Course: BMIT3173 Integrative Programming
 */

namespace App\Services\Donation;

use App\Models\Donation;
use App\Models\DonationItem;
use App\Services\Donation\Strategy\DonationSortStrategyResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DonationService
{
    public function list(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Donation::with(['donor', 'category', 'items']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['donor_id'])) {
            $query->where('donor_id', $filters['donor_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'newest';
        $strategy = DonationSortStrategyResolver::resolve($sortBy);
        $query = $strategy->sort($query);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function listAvailable(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $filters['status'] = 'available';

        $query = Donation::with(['donor', 'category', 'items'])
            ->where('status', 'available')
            ->where('is_active', true)
            ->where('expiry_date', '>=', now()->toDateString());

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'expiry_date';
        $strategy = DonationSortStrategyResolver::resolve($sortBy);
        $query = $strategy->sort($query);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Donation
    {
        return Donation::with(['donor', 'category', 'items'])->findOrFail($id);
    }

    public function create(array $data, ?UploadedFile $image = null): Donation
    {
        return DB::transaction(function () use ($data, $image) {
            if ($image) {
                $data['image_path'] = $image->store('donations', 'public');
            }

            $donation = Donation::create($data);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    DonationItem::create([
                        'donation_id' => $donation->id,
                        ...$item,
                    ]);
                }
            }

            return $donation->load(['donor', 'category', 'items']);
        });
    }

    public function update(Donation $donation, array $data, ?UploadedFile $image = null): Donation
    {
        return DB::transaction(function () use ($donation, $data, $image) {
            if ($image) {
                if ($donation->image_path) {
                    Storage::disk('public')->delete($donation->image_path);
                }

                $data['image_path'] = $image->store('donations', 'public');
            }

            $donation->update($data);
            $donation->refresh();

            $availableQuantity = $donation->availableQuantity();

            if (
                $availableQuantity > 0
                && $donation->is_active
                && ! $donation->isExpired()
            ) {
                $donation->update([
                    'status' => 'available',
                ]);
            } elseif ($availableQuantity <= 0) {
                $donation->update([
                    'status' => 'reserved',
                ]);
            }

            if (isset($data['items'])) {
                $donation->items()->delete();

                foreach ($data['items'] as $item) {
                    DonationItem::create([
                        'donation_id' => $donation->id,
                        ...$item,
                    ]);
                }
            }

            return $donation->fresh()->load([
                'donor',
                'category',
                'items',
            ]);
        });
    }

    public function delete(Donation $donation): void
    {
        if ($donation->image_path) {
            Storage::disk('public')->delete($donation->image_path);
        }
        $donation->delete();
    }

    public function updateStatus(Donation $donation, string $status): Donation
    {
        $donation->update(['status' => $status]);

        return $donation->fresh();
    }

    public function expireDonations(): int
    {
        return Donation::where('expiry_date', '<', now()->toDateString())
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired', 'is_active' => false]);
    }
}
