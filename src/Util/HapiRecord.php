<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

use \ArrayAccess;
use \JsonSerializable;

/**
 * Wrapper to allow array access to a HAPI record using parameter names instead of indices
 */
class HapiRecord implements ArrayAccess, JsonSerializable
{
    private array $record;
    private array $parameters;

    public function __construct(array &$record, array &$parameters)
    {
        $this->record = &$record;
        $this->parameters = &$parameters;
    }

    /**
     * Returns the value of the given field
     * if the given field matches the parameter's "fill" value, then null is returned as this means there's no data for that field.
     * @param string $name The name of the field to get.
     * @param mixed The value of the field, or null if the field matches the parameter's "fill" value, or null if $name is not a parameter
     */
    private function get(string $name): mixed
    {
        // Get the index of the given field
        $index = array_search($name, array_column($this->parameters, 'name'));
        // If the field was not found, throw an exception
        if ($index === false) {
            return null;
        }
        // Get the value of the field
        $value = $this->record[$index];
        // If the value matches the parameter's "fill" value, return null
        if ($value == $this->parameters[$index]['fill']) {
            return null;
        }
        // Otherwise, return the value
        return $value;
    }

    public function __get(string $name): mixed {
        return $this[$name];
    }

    public function __isset(string $name): bool {
        return $this->offsetExists($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        $index = array_search($offset, array_column($this->parameters, 'name'));
        return $index !== false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \Exception("HapiRecord is read-only");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \Exception("HapiRecord is read-only");
    }

    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->parameters as $parameter) {
            $result[$parameter['name']] = $this->get($parameter['name']);
        }
        return $result;
    }
}