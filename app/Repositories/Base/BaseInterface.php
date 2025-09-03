<?php

namespace App\Repositories\Base;

interface BaseInterface
{
    public function getAll(
        int $limit = 20,
        array $relations = [],
        array $filters = [],
        array $sort = []
    );
    public function find($id, array $relations = []);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getByField(string $field, $value, array $relations = [], array $withCount = [], int $limit = 20);

}
