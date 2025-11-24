<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentResponse extends Model
{
    protected $guarded = ['id'];

    public function question()
    {
        return $this->belongsTo(MasterQuestion::class, 'master_question_id');
    }
}