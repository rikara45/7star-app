<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterQuestion extends Model
{
    protected $fillable = ['dimension', 'statement_self', 'statement_other'];
}
