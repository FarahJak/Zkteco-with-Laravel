<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Rats\Zkteco\Lib\ZKTeco;

class ZktecoController extends Controller
{
    /**
     *
     * Get a list of the available attendance in the device.
     *
     */
    public function index()
    {
        $zk = new ZKTeco('192.168.1.19', 4370);

        if ($zk->connect()) {
            $attendance = $zk->getAttendance();
            return  $attendance;
        }
    }

    public function calculateAttendanceTime(Request $request)
    {
        // Create a new instance of the Zkteco class
        $zk = new Zkteco('192.168.1.19', 4370);

        // Check if the connection to the device was successful. If not, return an error message.
        if (!$zk->connect()) {
            return 'Unable to connect to the device.';
        }

        // Parse the specific date provided in the request and format it as 'Y-m-d'.
        $formattedSpecificDate = $this->changeDateFormat($request);

        // Filter the attendance data based on the specific date and user ID.
        $AttendanceRecordsAfterFiltering = $this->filterTheAttendanceData($zk, $request->userId, $formattedSpecificDate);

        // Compare And Remove Extra Attendance Records.
        $newAttendanceRecords = $this->removeExtraAttendanceRecords($AttendanceRecordsAfterFiltering);

        // Check the number of attendance records found for the specified date.
        $result = $this->checkAttendanceRecordsCount($newAttendanceRecords);

        return $result;
    }

    /**
     *
     * Filter the attendance data based on the specific date and user ID.
     *
     */
    public function changeDateFormat($request)
    {
        $date = DateTime::createFromFormat('m/d/Y',  $request->specificDate);
        $formattedSpecificDate = $date->format('Y-m-d');
        return $formattedSpecificDate;
    }

    /**
     *
     * Filter the attendance data based on the specific date and user ID.
     *
     */
    public function filterTheAttendanceData($zk, $userId, $formattedSpecificDate)
    {
        $filteredRecords = array_filter(
            $zk->getAttendance(),
            function ($attendance) use ($userId, $formattedSpecificDate) {
                $attendanceDate = date('Y-m-d', strtotime($attendance['timestamp']));
                return $attendanceDate == $formattedSpecificDate && $attendance['id'] == $userId;
            }
        );
        return $filteredRecords;
    }

    /**
     *
     * This function compares the timestamps in the array
     * and removes any attendance records that have a timestamp
     * difference of less than 60 seconds with the previous record.
     *
     */
    public function removeExtraAttendanceRecords($data)
    {

        $newArray = [];
        $previousTimestamp = null;

        foreach ($data as $key => $value) {
            $currentTimestamp = strtotime($value['timestamp']);

            if ($previousTimestamp !== null && ($currentTimestamp - $previousTimestamp) < 120) {
                unset($data[$key]);
            } else {
                $newArray[$key] = $value;
            }

            $previousTimestamp = $currentTimestamp;
        }

        return $newArray;
    }

    /**
     *
     * Check the number of attendance records found for the specified date.
     *
     */
    public function checkAttendanceRecordsCount($newAttendanceRecords)
    {
        $count = count($newAttendanceRecords);

        if ($count == 0) {
            return 'No attendance found for the specified date.';
        }
        if ($count == 1) {
            return 'Only one attendance found for the specified date.';
        }
        if ($count % 2 == 0 && $count >= 2) {
            $result = $this->calculateTimeDifferencesForEachPair($newAttendanceRecords, $count);
            return  "The attendance time for the requested user on the specified date is" . ' ' . $result . ' ' . "minutes";
        }

        return 'The number of attendance records found for the specified date is odd.';  // Return specific message for odd count
    }

    /**
     *
     * Calculate time differences for each pair of the attendance records
     *
     */
    public function calculateTimeDifferencesForEachPair($newAttendanceRecords, $count)
    {
        sort($newAttendanceRecords);

        for ($i = 0; $i < $count; $i += 2) {

            $currentTimestamp = strtotime($newAttendanceRecords[$i]['timestamp']);
            $nextTimestamp = strtotime($newAttendanceRecords[$i + 1]['timestamp']);

            $timeDifference = round(($nextTimestamp - $currentTimestamp) / (60));

            $timeDifferences[] = $timeDifference;
        }

        // Sum up all the time differences
        $totalTimeDifference = array_sum($timeDifferences);

        return $totalTimeDifference;
    }
}
