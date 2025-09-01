<?php

namespace App\Repositories;

use App\Models\LicenseKey;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LicenseKeyRepository extends BaseRepository implements LicenseKeyRepositoryInterface
{
    protected function getModel(): Model
    {
        return new LicenseKey;
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

    public function getWithLicenses(string $uuid): ?LicenseKey
    {
        return $this->model->with(['licenses.product', 'licenses.activations'])
            ->where('uuid', $uuid)
            ->first();
    }
}
