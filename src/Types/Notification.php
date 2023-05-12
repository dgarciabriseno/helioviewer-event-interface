<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

class Notification
{
    public string $id;
    public string $type;
    public string $url;
    public string $timestamp;
    public string $content;
}