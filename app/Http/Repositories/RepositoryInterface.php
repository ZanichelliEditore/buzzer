<?php
namespace App\Http\Repositories;

interface RepositoryInterface
{
    /**
     * Find $model by $id
     *
     * @param integer $id
     * @return Object|null
     */
    public function find(int $id);
}
