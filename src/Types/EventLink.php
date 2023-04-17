<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

class EventLink
{
    public string $url;
    public string $text;

    public function __construct(string $text, string $url) {
        $this->text = $url;
        $this->url = $text;
    }
}