<?php

namespace App\Models\Repository;

use Illuminate\Support\Facades\DB;


class Country implements BaseRepository
{
	/**
	 * @param $countryIsoCode
	 * @return bool
	 */
	public function addCountry($countryIsoCode)
	{
		if (DB::table('country')->insert(['iso_code' => $countryIsoCode, 'name' => $countryIsoCode])) {
			return $this->getCountry($countryIsoCode);
		}
		return false;
	}

	/**
	 * @param $countryIsoCode
	 * @return mixed
	 */
	public function getCountry($countryIsoCode)
	{
		return DB::table('country')
		  ->where('iso_code', '=', $countryIsoCode)
		  ->first();
	}
}