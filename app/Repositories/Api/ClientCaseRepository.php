<?php

namespace App\Repositories\Api;

use App\Models\ClientCase;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Auth;

class ClientCaseRepository extends BaseRepository
{
    public function model()
    {
        return ClientCase::class;
    }

    public function Store($data)
    {
        return $this->model->create($data);
    }

    public function GetUserCases($userId)
    {
        return $this->model->where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    public function GetCaseDetails($id, $userId)
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }
}
