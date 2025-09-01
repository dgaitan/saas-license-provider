<?php

namespace App\Repositories;

use App\Models\License;
use App\Repositories\Interfaces\LicenseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LicenseRepository implements LicenseRepositoryInterface
{
    public function __construct(
        private readonly License $model
    ) {}

    public function findByUuid(string $uuid): ?License
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByLicenseKeyId(int $licenseKeyId): Collection
    {
        return $this->model->where('license_key_id', $licenseKeyId)->get();
    }

    public function findByProductId(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    public function findByBrandId(int $brandId): Collection
    {
        return $this->model->whereHas('licenseKey', function ($query) use ($brandId) {
            $query->where('brand_id', $brandId);
        })->get();
    }

    public function create(array $data): License
    {
        return $this->model->create($data);
    }

    public function update(License $license, array $data): bool
    {
        return $license->update($data);
    }

    public function delete(License $license): bool
    {
        return $license->delete();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'valid')->get();
    }

    public function getWithRelationships(string $uuid): ?License
    {
        return $this->model->with(['licenseKey', 'product', 'activations'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByCustomerEmail(string $email): Collection
    {
        return $this->model->whereHas('licenseKey', function ($query) use ($email) {
            $query->where('customer_email', $email);
        })->with(['licenseKey.brand', 'product'])->get();
    }
}
