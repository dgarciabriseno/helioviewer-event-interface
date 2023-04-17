<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

class EventLink
{
    public string $link;
    public string $linkText;

    public function __construct(string $text, string $url) {
        $this->link = $url;
        $this->linkText = $text;
    }
}