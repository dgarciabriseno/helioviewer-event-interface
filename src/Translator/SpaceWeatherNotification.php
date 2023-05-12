<?php declare(strict_types=1);

namespace HelioviewerEventInterface\SpaceWeatherNotification;

use HelioviewerEventInterface\Types\Notification;
use HelioviewerEventInterface\Util\Date;
use HelioviewerEventInterface\Util\Subarray;

function Translate(array $notifications, mixed $extra, ?callable $postProcessor): array {
    $result = [];
    foreach ($notifications as $notif) {
        $notif = Subarray::Map($notif, [
            'messageType' => 'type',
            'messageID' => 'id',
            'messageURL' => 'url',
            'messageIssueTime' => 'timestamp',
            'messageBody' => 'content'
        ]);
        $notif['timestamp'] = Date::FormatString($notif['timestamp']);
        array_push($result, $notif);
    }

    $groups = [
        [
            'name' => 'DONKI Notifications',
            'contact' => 'gsfc-ccmc-support@lists.hq.nasa.gov',
            'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
            'data' => $result
        ]
    ];
    return $groups;
}