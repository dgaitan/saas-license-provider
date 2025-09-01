<?php

namespace App\Repositories;

use App\Models\Activation;
use App\Models\License;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ActivationRepository implements ActivationRepositoryInterface
{
    public function __construct(
        private readonly Activation $model
    ) {}

    public function findByUuid(string $uuid): ?Activation
    {
        return $this->model->where('uuid', $uuid)->first();
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

    public function create(array $data): Activation
    {
        return $this->model->create($data);
    }

    public function update(Activation $activation, array $data): bool
    {
        return $activation->update($data);
    }

    public function delete(Activation $activation): bool
    {
        return $activation->delete();
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
