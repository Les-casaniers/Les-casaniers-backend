<?php

namespace App\Repositories;

use App\Models\Admin;

class AdminRepository implements AdminRepositoryInterface
{
    public function getAll()
    {
        return Admin::all();
    }

    public function findById($id)
    {
        return Admin::find($id);
    }

    public function create(array $data)
    {
        return Admin::create($data);
    }

    public function update($id, array $data)
    {
        $admin = Admin::find($id);

        if ($admin) {
            $admin->update($data);
            return $admin;
        }

        return null;
    }

    public function delete($id)
    {
        $admin = Admin::find($id);

        if ($admin) {
            return $admin->delete();
        }

        return false;
    }

    public function findByEmail($email)
    {
        return Admin::where('email', $email)->first();
    }
}