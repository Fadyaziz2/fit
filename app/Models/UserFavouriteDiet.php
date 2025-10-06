<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Diet;

class UserFavouriteDiet extends Model
{
    use HasFactory;

    protected $fillable = [ 'user_id', 'diet_id' ];

    protected $casts = [
        'user_id'   => 'integer',
        'diet_id'   => 'integer',
    ];

    public function diet()
    {
        return $this->belongsTo(Diet::class);
    }
}
