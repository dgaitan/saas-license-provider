<?php

namespace App\Repositories;

use App\Models\LicenseKey;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LicenseKeyRepository implements LicenseKeyRepositoryInterface
{
    public function __construct(
        private readonly LicenseKey $model
    ) {}

    public function findByUuid(string $uuid): ?LicenseKey
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByKey(string $key): ?LicenseKey
    {
        return $this->model->where('key', $key)->first();
    }

    public function findByCustomerEmail(string $email): Collection
    {
        return $this->model->where('customer_email', $email)->get();
    }

    public function findByBrandId(int $brandId): Collection
    {
        return $this->model->where('brand_id', $brandId)->get();
    }

    public function create(array $data): LicenseKey
    {
        return $this->model->create($data);
    }

    public function update(LicenseKey $licenseKey, array $data): bool
    {
        return $licenseKey->update($data);
    }

    public function delete(LicenseKey $licenseKey): bool
    {
        return $licenseKey->delete();
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getWithLicenses(string $uuid): ?LicenseKey
    {
        return $this->model->with(['licenses.product', 'licenses.activations'])
            ->where('uuid', $uuid)
            ->first();
    }
}
