<?php

namespace App\Repositories;

use App\Models\Activation;
use App\Models\License;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivationRepository extends BaseRepository implements ActivationRepositoryInterface
{
    protected function getModel(): Model
    {
        return new Activation;
    }

    public function findByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)->get();
    }

    public function findActiveByLicenseId(int $licenseId): Collection
    {
        return $this->model->where('license_id', $licenseId)
            ->where('status', 'active')
            ->get();
    }

    public function findByLicenseAndInstance(
        License $license,
        ?string $instanceId = null,
        ?string $instanceType = null,
        ?string $instanceUrl = null,
        ?string $machineId = null
    ): ?Activation {
        $query = $this->model->where('license_id', $license->id);

        if ($instanceId) {
            $query->where('instance_id', $instanceId);
        }

        if ($instanceType) {
            $query->where('instance_type', $instanceType);
        }

        if ($instanceUrl) {
            $query->where('instance_url', $instanceUrl);
        }

        if ($machineId) {
            $query->where('machine_id', $machineId);
        }

        return $query->first();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function countActiveByLicenseId(int $licenseId): int
    {
        return $this->model->where('license_id', $licenseId)
            ->where('status', 'active')
            ->count();
    }
}
