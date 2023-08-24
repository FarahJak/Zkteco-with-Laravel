## About Zkteco

Zkteco is a brand that manufactures biometric devices such as fingerprint scanners, facial recognition devices, and access control systems. 

## About laravel Framework

Laravel is a popular PHP framework used for web application development.

## About Zkteco-with-Laravel

It is a demo project to integrate Zkteco devices with Laravel, in this project we use [raihanafroz/zkteco](https://github.com/raihanafroz/zkteco) package. It is a PHP library that provides an interface to communicate with Zkteco devices. It allows you to connect to the device, retrieve attendance logs, and perform other operations, such as:

- [Connecting to the ZKteco device](https://github.com/raihanafroz/zkteco#:~:text=Call%20ZKTeco%20methods-,Connect,-//%20%20%20%20connect%0A//%20%20%20%20this%20return).
- [Enabling the ZKteco device.](https://github.com/raihanafroz/zkteco#:~:text=%2D%3Edisconnect()%3B-,Enable%20Device,-//%20%20%20%20enable%0A//%20%20%20%20this%20return).
- [Getting the users](https://github.com/raihanafroz/zkteco#:~:text=%2D%3EsetTime()%3B-,Get%20Users,-//%20%20%20%20get%20User%0A//%20%20%20%20this).
- [Getting the Attendance Log](https://github.com/raihanafroz/zkteco#:~:text=%2D%3EremoveUser()%3B-,Get%20Attendance%20Log,-//%20%20%20%20get%20attendance%20log).


## Project Idea

The Idea of this demo project is to calculate the Attendance Time for a specific user at specific date.

## The steps to calculate the Attendance Time for a user:

### Main function:
-  Step 1: Create a new instance of the Zkteco class.
-  Step 2: Check if the connection to the device was successful. If not, return an error message.
-  Step 3: Parse the specific date provided in the request and format it as 'Y-m-d'.
-  Step 4: Filter the attendance data based on the specific date and user ID.
-  Step 5: Compare And Remove Extra Attendance Records.
-  Step 6: Check the number of attendance records found for the specified date.
-  Step 7: Return the result.

```php
use Rats\Zkteco\Lib\ZKTeco;

public function calculateAttendanceTime(Request $request)
{
    // Step 1:
    $zk = new Zkteco('192.168.1.19', 4370);

    // Step 2:
    if (!$zk->connect()) {
        return 'Unable to connect to the device.';
    }

    // Step 3:
    $formattedSpecificDate = $this->changeDateFormat($request);

    // Step 4:
    $AttendanceRecordsAfterFiltering = $this->filterTheAttendanceData($zk, $request->userId, $formattedSpecificDate);

    // Step 5:
    $newAttendanceRecords = $this->removeExtraAttendanceRecords($AttendanceRecordsAfterFiltering);

    // Step 6:
    $result = $this->checkAttendanceRecordsCount($newAttendanceRecords);

    // Step 7:
    return $result;
}
```
### Change Date Format function:

```php
use DateTime;

public function changeDateFormat($request)
{
    $date = DateTime::createFromFormat('m/d/Y',  $request->specificDate);
    $formattedSpecificDate = $date->format('Y-m-d');
    return $formattedSpecificDate;
}
```

### Filter The Attendance Data function:

```php

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
```

### Remove Extra Attendance Records function:

```php

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
```

### Check Attendance Records Count function:

```php

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
```

### Calculate Time Differences For Each Pair function:

```php

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
```
