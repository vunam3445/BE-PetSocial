<?php

namespace App\Repositories\Base;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseInterface 
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy danh sách dữ liệu với paginate, filter, sort, relations
     *
     * @param int $limit
     * @param array $relations
     * @param array $filters
     * @param array $sort
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(
        int $limit = 20,
        array $relations = [],
        array $filters = [],
        array $sort = []
    ) {
        $query = $this->model->with($relations);

        // Áp dụng filter
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        // Áp dụng sort
        foreach ($sort as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query->paginate($limit);
    }

    public function find($id, array $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        return $record->delete();
    }


    // lấy 
   public function getByField(
    string $field, 
    $value, 
    array $relations = [], 
    array $withCount = [], 
    int $limit = 20
) {
    $query = $this->model->with($relations);

    if (!empty($withCount)) {
        $query->withCount($withCount);
    }

    return $query
        ->where($field, $value)
        ->orderBy('updated_at', 'desc')
        ->paginate($limit);
}

}
