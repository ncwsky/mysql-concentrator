<?php

namespace MySQLConcentrator;

class MySQLConnection extends Connection
{
    public $client_authentication_packet = null;
    public $client_authentication_response_packet = null;
    public $client_queue = array();
    public $current_client = null;
    public $handshake_completed = false;
    public $handshake_init_packet = null;
    public $transaction_count = 0;

    function read()
    {
        parent::read();
        $this->read_packets();
        if (empty($this->packets_read)) {
//      $this->log("No packets\n");
            return;
        }
        while ($this->current_client == null) {
            $client = array_shift($this->client_queue);
            if ($client == null) {
                break;
            } elseif (!$client->closed) {
                $this->current_client = $client;
            }
        }
        foreach ($this->packets_read as $packet) {
//      $this->log("Read from MySQL " . $packet->type_name() . "\n");
            if ($this->handshake_init_packet == null) {
                $packet->type = Packet::HANDSHAKE_INITIALIZATION_PACKET;
                $this->handshake_init_packet = $packet;
//        $this->log("Read from MySQL handshake initialization packet.\n");
            } elseif ($this->client_authentication_response_packet == null) {
                $packet->parse('result', 'ok');
                if ($packet->type == Packet::RESPONSE_OK) {
                    $this->client_authentication_response_packet = $packet;
                    $this->handshake_completed = true;
                }
//        $this->log("Read from MySQL client authentication response packet.\n");
            }
            if ($this->current_client != null) {
                $this->current_client->queue_write_packet($packet);
            }
        }
        $this->packets_read = array();
//    $this->log($this->current_client->log_name() . ": " . $this->current_client->state . "\n");
        if ($this->current_client != null && $this->current_client->done_with_operation()) {
            $this->current_client->queued = false;
            $this->current_client = array_shift($this->client_queue);
        }
    }

    function remove($client_connection)
    {
        if ($this->current_client === $client_connection) {
            $this->current_client = null;
        }
        $index = array_search($client_connection, $this->client_queue);
        if ($index !== false) {
            unset($this->client_queue[$index]);
        }
    }

    function queue($client_connection)
    {
        if ($client_connection->queued) {
            return;
        }
        $client_connection->queued = true;
        $this->client_queue[] = $client_connection;
        if ($this->current_client == null) {
            $this->current_client = array_shift($this->client_queue);
        }
    }

    function wants_to_write()
    {
//    if ($this->current_client == NULL)
//    {
//      $this->log("Foo: NULL\n");
//    }
//    else
//    {
//      $this->log("Wants to Write: {$this->current_client->name}:{$this->current_client->address}:{$this->current_client->port}: " . empty($this->current_client->packets_read) . "\n");
//    }
        return ($this->current_client != null && !empty($this->current_client->packets_read)) || (!$this->write_buffer->is_empty());
    }

    function write()
    {
        if ($this->current_client == null) {
            if (empty($this->client_queue)) {
                return;
            }
            $this->current_client = $this->client_queue[0];
        }
        while ($packet = array_shift($this->current_client->packets_read)) {
            $this->write_buffer->append($packet->binary);
        }
        parent::write();
    }
}
