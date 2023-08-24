<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
