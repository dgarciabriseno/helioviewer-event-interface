<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

class DataSource {
    public string $name;
    protected string $url;
    protected string $startName;
    protected string $endName;
    protected string $dateFormat;
    protected string $translator;

    /**
     * Creates a new DataSource instance
     * @param string $name The name of this data source.
     * @param string $url The url of for the source's API.
     * @param string $startName The query string parameter name for the start date.
     * @param string $endName The query string parameter name for the end date.
     * @param string $dateFormat The format to use for the dates.
     * @param string $translator The name of the translator class to use for this data source.
     */
    public function __construct(string $name, string $url, string $startName, string $endName, string $dateFormat, string $translator) {
        $this->name = $name;
        $this->url = $url;
        $this->startName = $startName;
        $this->endName = $endName;
        $this->dateFormat = $dateFormat;
        $this->translator = $translator;
    }
}