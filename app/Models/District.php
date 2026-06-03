<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    //
    protected $guarded = [];
	protected $table = 'districts';

	public function users() {
		return $this->hasMany(users::class);
	}
}
