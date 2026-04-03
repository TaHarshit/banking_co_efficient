<?php

namespace App\Repositories\Admin;

use App\Models\EmailSubscribe;
use App\Repositories\BaseRepository;

class EmailSubRepository extends BaseRepository
{
    public function model()
    {
        return EmailSubscribe::class;
    }
}
