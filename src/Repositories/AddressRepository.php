<?php


namespace Twdd\Repositories;


use Twdd\Models\Address;

class AddressRepository
{
    private $model;

    public function __construct(Address $address)
    {
        $this->model = $address;
    }

    public function store(array $params)
    {
        return $this->model->newQuery()->create($params);
    }

    public function find(int $id)
    {
        return $this->model->newQuery()->find($id);
    }

    public function update(int $id, array $data)
    {
        return $this->model->newQuery()->where('id', $id)->update($data);
    }
}
