<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Repository\Country as CountryRepository;
use App\Models\Repository\Holiday as HolidayRepository;

class AddCountryHolidays extends Command
{

    const PARAM_FILE_PATH = 'filePath';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holiday:add-country-holiday {'.self::PARAM_FILE_PATH.'}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Specify full path of the JSON file. Name of the file should be ISO 3166-1 alpha-2 compliant country code with .json extension like GB.json';

    /**
     * AddCountryHolidays constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $result = $this->validateInput();
        if (is_array($result) && !empty($result)) {
            $countryRepository = new CountryRepository();
            $countryModel = $countryRepository->getCountry($result['isoCode']);
            $holidayRepository = new HolidayRepository();
            if (empty($countryModel)) {
                $countryModel = $countryRepository->addCountry($result['isoCode']);
            }

            if (!empty($countryModel)) {
                if (!empty($result['holidayData'])) {
                    foreach ($result['holidayData'] as $holidayDataRow) {
                        if (!empty($holidayDataRow['name']) && !empty($holidayDataRow['rule'])) {
                            if (!$holidayRepository->getHoliday($holidayDataRow['name'], $holidayDataRow['rule'], $countryModel->id)) {
                                $isBankHoliday = (!empty($holidayDataRow['bank_holiday'])) ? true : false;
                                $holidayRepository->addHoliday($holidayDataRow['name'], $holidayDataRow['rule'], $countryModel->id, $isBankHoliday);
                            }
                        }
                    }
                } else {
                    $this->warn('No holiday data provided');
                }
            } else {
                $this->error('Unable to setup country record');
            }
        }
    }

    /**
     * Method to validate the file path & JSON data.
     * @return array
     */
    protected function validateInput()
    {
        $filePath = $this->argument(self::PARAM_FILE_PATH);
        if (file_exists($filePath) && is_readable($filePath)) {
            $fileInfo = pathinfo($filePath);
            if (!empty($fileInfo['filename']) && strlen($fileInfo['filename']) == 2) {
                $fileContents = file_get_contents($filePath);
                $holidayData = @json_decode($fileContents, true);

                if (!json_last_error() && is_array($holidayData)) {
                    return [
                        'isoCode' => $fileInfo['filename'],
                        'holidayData' => $holidayData
                    ];
                } else {
                    $this->error('Malformed JSON data');
                }

            } else {
                $this->error('Invalid file name');
            }

        } else {
            $this->error('Invalid file path');
        }
    }
}
