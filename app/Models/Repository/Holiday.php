<?php

namespace App\Models\Repository;

use Illuminate\Support\Facades\DB;

class Holiday implements BaseRepository
{
	/**
	 * @param $name
	 * @param $rule
	 * @param $countryId
	 * @param bool $isBankHoliday
	 * @return mixed
	 */
	public function addHoliday($name, $rule, $countryId, $isBankHoliday = false)
	{
		return DB::table('holiday')->insert([
			'name' => trim($name),
			'rule' => trim($rule),
			'country_id' => $countryId,
			'bank_holiday' => $isBankHoliday,
		]);
	}

	/**
	 * @param $name
	 * @param $rule
	 * @param $countryId
	 * @return mixed
	 */
	public function getHoliday($name, $rule, $countryId)
	{
		return DB::table('holiday')
		  ->where('name', '=', trim($name))
		  ->where('rule', '=', trim($rule))
		  ->where('country_id', '=', $countryId)
		  ->first();
	}

}