<?php

namespace App\Repositories;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base repository providing common CRUD operations.
 *
 * This abstract class implements the BaseRepositoryInterface and provides
 * default implementations for common repository operations. Concrete
 * repository classes should extend this class and implement the getModel()
 * method to specify which model they work with.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The model instance for this repository.
     */
    protected Model $model;

    /**
     * Create a new repository instance.
     *
     * Initializes the model instance by calling the abstract getModel() method.
     */
    public function __construct()
    {
        $this->model = $this->getModel();
    }

    /**
     * Get the model instance for this repository.
     *
     * This method must be implemented by concrete repository classes
     * to specify which model they work with.
     *
     * @return Model The model instance
     */
    abstract protected function getModel(): Model;

    /**
     * Find a model by its UUID.
     *
     * @param  string  $uuid  The UUID of the model to find
     * @return Model|null The found model or null if not found
     */
    public function findByUuid(string $uuid): ?Model
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Create a new model instance.
     *
     * @param  array  $data  The data to create the model with
     * @return Model The created model instance
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing model instance.
     *
     * @param  Model  $model  The model instance to update
     * @param  array  $data  The data to update the model with
     * @return bool True if the update was successful, false otherwise
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete a model instance.
     *
     * @param  Model  $model  The model instance to delete
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Get all active model instances.
     *
     * @return Collection A collection of all active model instances
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }
}
