<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';

  	protected $fillable = [
	  	'iso_code', 'name'
	];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function holidays()
	{
		return $this->hasMany('App\Models\Holiday');
	}
}
