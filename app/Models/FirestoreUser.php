<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirestoreUser extends Model
{
    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;
}
