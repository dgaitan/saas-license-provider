<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository interface defining common CRUD operations.
 *
 * This interface provides a contract for basic repository operations
 * that are common across all repository implementations.
 */
interface BaseRepositoryInterface
{
    /**
     * Find a model by its UUID.
     *
     * @param  string  $uuid  The UUID of the model to find
     * @return Model|null The found model or null if not found
     */
    public function findByUuid(string $uuid): ?Model;

    /**
     * Create a new model instance.
     *
     * @param  array  $data  The data to create the model with
     * @return Model The created model instance
     */
    public function create(array $data): Model;

    /**
     * Update an existing model instance.
     *
     * @param  Model  $model  The model instance to update
     * @param  array  $data  The data to update the model with
     * @return bool True if the update was successful, false otherwise
     */
    public function update(Model $model, array $data): bool;

    /**
     * Delete a model instance.
     *
     * @param  Model  $model  The model instance to delete
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(Model $model): bool;

    /**
     * Get all active model instances.
     *
     * @return Collection A collection of all active model instances
     */
    public function getActive(): Collection;
}
