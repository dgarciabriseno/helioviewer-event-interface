<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Coordinator;

use \Socket;

class Hgs2Hpc {
    private Socket $socket;
    private bool $connected = false;
    public function __construct() {
        $this->connectToPythonServer();
    }

    function __destruct() {
        if ($this->connected) {
            socket_write($this->socket, "quit");
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
    }

    /**
     * Opens a socket connection to the python server which will perform the coordinate transforms
     */
    private function connectToPythonServer() {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (socket_connect($this->socket, "/tmp/hgs2hpc.sock")) {
            $this->connected = true;
        } else {
            error_log("Failed to connect to the python Hgs2Hpc server. Check that it's running and that permissions allow the user running PHP to read and write to it.");
        }
    }

    /**
     * Converts latitude and longitude coordinates at the given time to HelioProjective coordinates
     */
    public function convert(float $latitude, float $longitude, string $date) {
        try {
            if ($this->connected) {
                $msg = "$latitude $longitude $date";
                $this->write($msg);
                $result = $this->read();
                return $this->parseHpcResponse($result);
            } else {
                // If the socket couldn't connect, log it and return something.
                // Returning 0's is going to make it very obvious that something is wrong... for better or worse.
                // Returning anything actually is going to make it very obvious that something is wrong.
                return array("x" => 0, "y" => 0);
            }
        } catch (Exception $e) {
            // On exception, log it and return something.
            error_log($e->getMessage());
            return array("x" => 0, "y" => 0);
        }
    }

    /**
     * Attempts to write to the socket. Throws an exception on failure
     */
    private function write(string $msg) {
        $success = socket_write($this->socket, $msg);
        if ($success === false) {
            $this->throwSocketError();
        }
    }

    /**
     * Attempts to read from the socket. Throws an exception on failure
     */
    private function read() {
        $result = socket_read($this->socket, 1024);
        if ($result === false || $result === "") {
            $this->throwSocketError();
        }
        return $result;
    }

    /**
     * Throws an exception with the error message provided by the socket interface.
     */
    private function throwSocketError() {
        throw new Exception(socket_strerror(socket_last_error($this->socket)));
    }

    /**
     * Parses the response returned by the python server containing HPC coordinates
     */
    public function parseHpcResponse(string $response) {
        $result = explode(" ", $response);
        return array("x" => floatval($result[0]), "y" => floatval($result[1]));
    }
}