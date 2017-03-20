<?php

namespace App\Services\Holiday;

use App\Services\Holiday\ApiInterface as HolidayApiInterface;

use App\Models\Country as CountryModel;
use App\Models\Holiday as HolidayModel;


class ApiV1Service implements HolidayApiInterface
{
    private $cache = false;

    /**
     * @param $params
     * @return array
     */
    public function validateInput($params)
    {
        if (empty($params['country'])) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => 'The country parameter is required.'
            ];
            return $jsonResponseBody;
        }

        if (empty($params['year'])) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => 'The year parameter is required.'
            ];
            return $jsonResponseBody;
        }

        if (!empty($params['previous']) && !empty($params['upcoming'])) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => 'You cannot request both previous and upcoming holidays.'
            ];
            return $jsonResponseBody;
        } else if ((!empty($params['previous']) || !empty($params['upcoming'])) && (empty($params['month']) || empty($params['day']))) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => 'The ' . (empty($params['month']) ? 'month' : 'day') . ' parameter is required when requesting '.((!empty($params['previous'])) ? 'previous' : 'upcoming').' holidays.'
            ];
            return $jsonResponseBody;
        }

        if (!empty($params['month']) && !empty($params['day'])) {
            $date = $params['year'] . '-' . $params['month'] . '-' . $params['day'];
            if (strtotime($date) === false) {
                $jsonResponseBody = [
                  'status' => 400,
                  'error' => 'The supplied date (' . $date . ') is invalid.'
                ];
                return $jsonResponseBody;
            }
        }
    }

    /**
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function getHolidays($params = array())
    {
        $validationResult = $this->validateInput($params);

        if (!empty($validationResult)) {
            return $validationResult;
        }

        $countryModel = CountryModel::where('iso_code', $params['country'])->first();
        if (empty($countryModel)) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => 'The supplied country ('.$params['country'].') is not supported at this time.'
            ];
            return $jsonResponseBody;
        }

        $previous = (!empty($params['previous'])) ? $params['previous'] : null;
        $upcoming = (!empty($params['upcoming'])) ? $params['upcoming'] : null;
        $bankHolidays = (!empty($params['bankHolidays'])) ? true : false;
        $month = (isset($params['month'])) ? str_pad($params['month'], 2, '0', STR_PAD_LEFT) : '';
        $day = (isset($params['day'])) ? str_pad(day['day'], 2, '0', STR_PAD_LEFT) : '';

        try {
            $countryHolidays = $this->calculateHolidays($countryModel, $params['year'], $previous || $upcoming, $bankHolidays);
        } catch (\Exception $e) {
            $jsonResponseBody = [
              'status' => 400,
              'error' => $e->getMessage()
            ];
            return $jsonResponseBody;
        }

        $jsonResponseBody = [
          'status' => 200,
          'holidays' => []
        ];

        if (!empty($countryHolidays)) {
            $jsonResponseBody['holidays'] = [];

            if (!empty($month) && !empty($day)) {
                $date = $params['year'] . '-' . $month . '-' . $day;
                if ($previous) {
                    $countryHolidays = $this->flatten($date, $countryHolidays[$params['year'] - 1], $countryHolidays[$params['year']]);
                    prev($countryHolidays);
                    $jsonResponseBody['holidays'] = current($countryHolidays);
                } elseif ($upcoming) {
                    $countryHolidays = $this->flatten($date, $countryHolidays[$params['year']], $countryHolidays[$params['year'] + 1]);
                    next($countryHolidays);
                    $jsonResponseBody['holidays'] = current($countryHolidays);
                } elseif (isset($countryHolidays[$params['year']][$date])) {
                    $jsonResponseBody['holidays'] = $countryHolidays[$params['year']][$date];
                }
            } elseif (!empty($month)) {
                foreach ($countryHolidays[$params['year']] as $date => $countryHoliday) {
                    if (substr($date, 0, 7) == $params['year'] . '-' . $month) {
                        $jsonResponseBody['holidays'] = array_merge($jsonResponseBody['holidays'], $countryHoliday);
                    }
                }
            } else {
                $jsonResponseBody['holidays'] = $countryHolidays[$params['year']];
            }
        }

        return $jsonResponseBody;
    }

    /**
     * @param $countryModel
     * @param $year
     * @param bool $range
     * @param bool $bankHolidays
     * @return array
     */
    private function calculateHolidays($countryModel, $year, $range = false, $bankHolidays = false)
    {
        $return = [];

        if ($range) {
            $years = [$year - 1, $year, $year + 1];
        } else {
            $years = [$year];
        }

        foreach ($years as $year) {
            // Cache is not configured at this time
            if ($this->cache) {
                $cacheKey        = 'holidayapi:' . $countryModel . ':holidays:' . $year;
                $countryHolidays = $this->cache->get($cacheKey);
            } else {
                $countryHolidays = false;
            }

            if ($countryHolidays) {
                $countryHolidays = unserialize($countryHolidays);
            } else {
                //Fetch bank holiday only for current Country model
                if ($bankHolidays) {
                    $holidayModels = HolidayModel::where('country_id', $countryModel->id)
                        ->where('bank_holiday', 1)
                        ->get();
                } else {
                    $holidayModels = $countryModel->holidays()->getResults();
                }

                $countryHolidays    = $this->convertDBHolidaysToArray($holidayModels);
                $calculatedHolidays = [];

                foreach ($countryHolidays as $countryHoliday) {
                    if (strstr($countryHoliday['rule'], '%Y')) {
                        $rule = str_replace('%Y', $year, $countryHoliday['rule']);
                    } elseif (strstr($countryHoliday['rule'], '%EASTER')) {
                        $rule = str_replace('%EASTER', date('Y-m-d', strtotime($year . '-03-21 +' . easter_days($year) . ' days')), $countryHoliday['rule']);
                    } elseif (in_array($countryModel->iso_code, ['BR', 'US']) && strstr($countryHoliday['rule'], '%ELECTION')) {
                        switch ($countryModel->iso_code) {
                            case 'BR':
                                $years = range(2014, $year, 2);
                                break;
                            case 'US':
                                $years = range(1788, $year, 4);
                                break;
                        }

                        if (in_array($year, $years)) {
                            $rule = str_replace('%ELECTION', $year, $countryHoliday['rule']);
                        } else {
                            $rule = false;
                        }
                    } else {
                        $rule = $countryHoliday['rule'] . ' ' . $year;
                    }

                    if ($rule) {
                        $calculatedDate = date('Y-m-d', strtotime($rule));

                        if (!isset($calculatedHolidays[$calculatedDate])) {
                            $calculatedHolidays[$calculatedDate] = [];
                        }

                        $calculatedHolidays[$calculatedDate][] = [
                          'name'    => $countryHoliday['name'],
                          'country' => $countryModel->iso_code,
                          'date'    => $calculatedDate,
                        ];
                    }
                }

                $countryHolidays = $calculatedHolidays;

                ksort($countryHolidays);

                foreach ($countryHolidays as $dateKey => $dateHolidays) {
                    usort($dateHolidays, function($a, $b)
                    {
                        $a = $a['name'];
                        $b = $b['name'];

                        if ($a == $b) {
                            return 0;
                        }

                        return $a < $b ? -1 : 1;
                    });

                    $countryHolidays[$dateKey] = $dateHolidays;
                }

                if ($this->cache) {
                    $this->cache->setex($cacheKey, 3600, serialize($countryHolidays));
                }
            }

            $return[$year] = $countryHolidays;
        }

        return $return;
    }

    /**
     * Copy of code from https://github.com/joshtronic
     * @param $date
     * @param $array1
     * @param $array2
     * @return array
     */
    private function flatten($date, $array1, $array2)
    {
        $holidays = array_merge($array1, $array2);

        // Injects the current date as a placeholder
        if (!isset($holidays[$date])) {
            $holidays[$date] = false;
            ksort($holidays);
        }

        // Sets the internal pointer to today
        while (key($holidays) !== $date) {
            next($holidays);
        }

        return $holidays;
    }

    /**
     * Method to convert the Eloquent collection to array
     * @param $holidays \Illuminate\Database\Eloquent\Collection
     * @return array
     */
    private function convertDBHolidaysToArray($holidays)
    {
        $countryHolidays = [];

        if (!empty($holidays)) {
            foreach ($holidays as $holiday) {
                array_push($countryHolidays, [
                  'name' => $holiday->name,
                  'rule' => $holiday->rule,
                  'bank_holiday' => ((!empty($holiday->bank_holiday)) ? true :false),
                ]);
            }
        }
        return $countryHolidays;
    }

}
