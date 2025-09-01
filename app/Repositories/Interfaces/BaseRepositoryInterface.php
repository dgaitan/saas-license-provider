<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * Find a model by UUID.
     */
    public function findByUuid(string $uuid): ?Model;

    /**
     * Create a new model.
     */
    public function create(array $data): Model;

    /**
     * Update a model.
     */
    public function update(Model $model, array $data): bool;

    /**
     * Delete a model.
     */
    public function delete(Model $model): bool;

    /**
     * Get all active models.
     */
    public function getActive(): Collection;
}
