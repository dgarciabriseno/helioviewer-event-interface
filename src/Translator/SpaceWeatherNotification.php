<?php declare(strict_types=1);

namespace HelioviewerEventInterface\SpaceWeatherNotification;

use DateTimeImmutable;
use DateTimeInterface;
use HelioviewerEventInterface\Types\Notification;
use HelioviewerEventInterface\Util\Date;
use HelioviewerEventInterface\Util\Subarray;

function Translate(array $notifications, mixed $extra, ?callable $postProcessor, DateTimeInterface $start, DateTimeInterface $end): array {
    $result = [];
    foreach ($notifications as $notif) {
        // This API returns data outside the requested time range, so grab the timestamp and only process notifications within the given time range.
        $notificationTime = new DateTimeImmutable($notif['messageIssueTime']);
        if ($notificationTime >= $start && $notificationTime <= $end) {
            // Map fields from the notification object over to the standard keys that the Helioviewer client understands
            $notif = Subarray::Map($notif, [
                'messageType' => 'type',
                'messageID' => 'id',
                'messageURL' => 'url',
                'messageIssueTime' => 'timestamp',
                'messageBody' => 'content'
            ]);
            // Format the date string into a standard Helioviewer date (Y-m-d hour:minute:second)
            $notif['timestamp'] = Date::FormatDate($notificationTime); // Date::FormatString($notif['timestamp']);
            // Create the label that will appear in the UI
            $notif['label'] = CreateNotificationLabel($notif, $notif['timestamp']);
            array_push($result, $notif);
        }
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

/**
 * Creates the label that appears on helioviewer.org for this notification
 */
function CreateNotificationLabel(array $notification, string $timeString): string {
    return $notification['type'] . " @ " . $timeString;
}