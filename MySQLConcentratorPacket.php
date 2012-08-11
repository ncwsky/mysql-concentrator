<?php

class MySQLConcentratorPacket
{
  const HANDSHAKE_INITIALIZATION_PACKET = 0xffff;
  const CLIENT_AUTHENTICATION_PACKET = 0xfffe;
  const RESPONSE_OK = 0xfffd;
  const RESPONSE_ERROR = 0xfffc;
  const RESPONSE_RESULT_SET = 0xfffb;
  const RESPONSE_FIELD = 0xfffa;
  const RESPONSE_ROW_DATA = 0xfff9;
  const RESPONSE_EOF = 0xfff8;
  const COM_SLEEP = 0;
  const COM_QUIT = 1;
  const COM_INIT_DB = 2;
  const COM_QUERY = 3;
  const COM_FIELD_LIST = 4;
  const COM_CREATE_DB = 5;
  const COM_DROP_DB = 6; 
  const COM_REFRESH = 7;
  const COM_SHUTDOWN = 8; 
  const COM_STATISTICS = 9;
  const COM_PROCESS_INFO = 10;
  const COM_CONNECT = 11;
  const COM_PROCESS_KILL = 12;
  const COM_DEBUG = 13;
  const COM_PING = 14;
  const COM_TIME = 15;
  const COM_DELAYED_INSERT = 16;
  const COM_CHANGE_USER = 17;
  const COM_BINLOG_DUMP = 18;
  const COM_TABLE_DUMP = 19;
  const COM_CONNECT_OUT = 20;
  const COM_REGISTER_SLAVE = 21;
  const COM_STMT_PREPARE = 22;
  const COM_STMT_EXECUTE = 23;
  const COM_STMT_SEND_LONG_DATA = 24;
  const COM_STMT_CLOSE = 25;
  const COM_STMT_RESET = 26;
  const COM_SET_OPTION = 27;
  const COM_STMT_FETCH = 28;
  const COM_DAEMON = 29;

  static $command_id_to_string = array
  (
    MySQLConcentratorPacket::COM_SLEEP => 'sleep',
    MySQLConcentratorPacket::COM_QUIT => 'quit',
    MySQLConcentratorPacket::COM_INIT_DB => 'init_db',
    MySQLConcentratorPacket::COM_QUERY => 'query',
    MySQLConcentratorPacket::COM_FIELD_LIST => 'field_list',
    MySQLConcentratorPacket::COM_CREATE_DB => 'create_db',
    MySQLConcentratorPacket::COM_DROP_DB => 'drop_db',
    MySQLConcentratorPacket::COM_REFRESH => 'refresh',
    MySQLConcentratorPacket::COM_SHUTDOWN => 'shutdown',
    MySQLConcentratorPacket::COM_STATISTICS => 'statistics',
    MySQLConcentratorPacket::COM_PROCESS_INFO => 'process_info',
    MySQLConcentratorPacket::COM_CONNECT => 'connect',
    MySQLConcentratorPacket::COM_PROCESS_KILL => 'process_kill',
    MySQLConcentratorPacket::COM_DEBUG => 'debug',
    MySQLConcentratorPacket::COM_PING => 'ping',
    MySQLConcentratorPacket::COM_TIME => 'time',
    MySQLConcentratorPacket::COM_DELAYED_INSERT => 'delayed_insert',
    MySQLConcentratorPacket::COM_CHANGE_USER => 'change_user',
    MySQLConcentratorPacket::COM_BINLOG_DUMP => 'binlog_dump',
    MySQLConcentratorPacket::COM_TABLE_DUMP => 'table_dump',
    MySQLConcentratorPacket::COM_CONNECT_OUT => 'connect_out',
    MySQLConcentratorPacket::COM_REGISTER_SLAVE => 'register_slave',
    MySQLConcentratorPacket::COM_STMT_PREPARE => 'stmt_prepare',
    MySQLConcentratorPacket::COM_STMT_EXECUTE => 'stmt_execute',
    MySQLConcentratorPacket::COM_STMT_SEND_LONG_DATA => 'stmt_send_log_data',
    MySQLConcentratorPacket::COM_STMT_CLOSE => 'stmt_close',
    MySQLConcentratorPacket::COM_STMT_RESET => 'stmt_reset',
    MySQLConcentratorPacket::COM_SET_OPTION => 'set_option',
    MySQLConcentratorPacket::COM_STMT_FETCH => 'stmt_fetch',
    MySQLConcentratorPacket::COM_DAEMON => 'daemon',
  );

  public $attributes = array();
  public $binary = null;
  public $length = null;
  public $number = null;
  public $type = null;

  function __construct($binary = null)
  {
    $this->binary = $binary;
  }

  function parse($expected)
  {
    list($this->length, $this->number) = self::parse_header($this->binary);
    $method = "parse_$expected";
    $this->$method(func_get_args());
  }

  function parse_closing_string($attribute_name)
  {
    $this->attributes[$attribute_name] = substr($this->binary, $this->parse_position);
    $this->parse_position = $this->length;
  }

  function parse_com_query()
  {
    $this->parse_closing_string('statement');
  }

  function parse_com_quit()
  {
  }

  function parse_command()
  {
    $first_byte = ord($this->binary{4});
    $this->type = $first_byte;
    $this->parse_position = 5;
    if (array_key_exists($first_byte, self::$command_id_to_string))
    {
      $command_name = self::$command_id_to_string[$first_byte];
    }
    else
    {
      return "don't know how to parse command with id '$first_byte'";
    }
    $method_name = "parse_com_$command_name";
    $this->$method_name(); 
  }

  function parse_eof()
  {
    $this->parse_position = 5;
    $this->parse_next_2_byte_integer('warning_count');
    $this->parse_next_2_byte_integer('status_flags');
  }

  function parse_field()
  {
    $this->type = self::RESPONSE_FIELD;
    $this->parse_position = 4;
    $this->parse_next_length_coded_string('catalog'); 
    $this->parse_next_length_coded_string('db'); 
    $this->parse_next_length_coded_string('table'); 
    $this->parse_next_length_coded_string('org_table'); 
    $this->parse_next_length_coded_string('name'); 
    $this->parse_next_length_coded_string('org_name'); 
    $this->parse_position += 1;
    $this->parse_next_2_byte_integer('charsetnr'); 
    $this->parse_next_4_byte_integer('length'); 
    $this->parse_next_1_byte_integer('type'); 
    $this->parse_next_2_byte_integer('flags'); 
    $this->parse_next_1_byte_integer('decimals'); 
    $this->parse_position += 2;
    $this->parse_next_length_coded_binary('default'); 
  }

  static function parse_header($binary)
  {
    $length = self::unmarshall_little_endian_integer($binary, 3);
    $number = ord($binary{3});
    return array($length, $number);
  }

  function parse_next_1_byte_integer($attribute_name)
  {
    $value = ord($this->binary{$this->parse_position});
    $this->attributes[$attribute_name] = $value;
    $this->parse_position += 1;
  }

  function parse_next_2_byte_integer($attribute_name)
  {
    $value = self::unmarshall_little_endian_integer($this->binary, 2, $this->parse_position);
    $this->attributes[$attribute_name] = $value;
    $this->parse_position += 2;
  }

  function parse_next_4_byte_integer($attribute_name)
  {
    $value = self::unmarshall_little_endian_integer($this->binary, 2, $this->parse_position);
    $this->attributes[$attribute_name] = $value;
    $this->parse_position += 4;
  }

  function parse_next_length_coded_binary($attribute_name)
  {
    list($value, $length) = self::unmarshall_length_coded_binary(substr($this->binary, $this->parse_position));
    $this->attributes[$attribute_name] = $value;
    $this->parse_position += $length;
  }

  function parse_next_length_coded_string($attribute_name)
  {
    list($value, $length) = self::unmarshall_length_coded_string(substr($this->binary, $this->parse_position));
    $this->attributes[$attribute_name] = $value;
    $this->parse_position += $length;
  }

  function parse_ok()
  {
    $this->parse_next_length_coded_binary('field_count');
    $this->parse_next_length_coded_binary('affected_rows');
    $this->parse_next_length_coded_binary('insert_id');
    $this->parse_next_2_byte_integer('server_status');
    $this->parse_next_2_byte_integer('warning_count');
    $this->parse_closing_string('message');
  }

  function parse_result()
  {
    $first_byte = ord($this->binary{4});
    $this->parse_position = 4;
    switch ($first_byte)
    {
      case 0x0:
        $this->type = self::RESPONSE_OK;
        $this->parse_ok();
        break;
      case 0xff:
        $this->type = self::RESPONSE_ERROR;
        $this->parse_error();
        break;
      case 0xfe:
        $this->type = self::RESPONSE_EOF;
        $this->parse_eof();
        break;
      default:
        $this->type = self::RESPONSE_RESULT_SET;
        $this->parse_result_set();
        break;
    }
  }

  function parse_result_set()
  {
    $this->parse_next_length_coded_binary('field_count');
    $this->parse_next_length_coded_binary('extra');
  }

  function parse_row_data($args)
  {
    $this->type = self::RESPONSE_ROW_DATA;
    $this->parse_position = 4;
    $num_columns = $args[1];
    $result = array();
    for ($i = 0; $i < $num_columns; $i++)
    {
      list($value, $length) = self::unmarshall_length_coded_string(substr($this->binary, $this->parse_position));
      $result[] = $value;
      $this->parse_position += $length;
    }
    $this->attributes['column_data'] = $result;
  }

  static function unmarshall_little_endian_integer($binary, $length, $offset = 0)
  {
    $bits = 0;
    $result = 0;
    for ($i = 0 + $offset; $i < $offset + $length; $i++)
    {
      $result += ord($binary{$i}) << $bits;
      $bits += 8;
    }
    return $result;
  }

  static function unmarshall_length_coded_binary($binary)
  {
    $first_byte = ord($binary{0});
    switch ($first_byte)
    {
      case 252:
        $length = 3;
        break;
      case 253:
        $length = 4;
        break;
      case 254:
        $length = 9;
        break;
      default:
        $length = 1;
    }
    if ($length == 1)
    {
      if ($first_byte == 251)
      {
        return array(null, $length);
      }
      else
      {
        return array($first_byte, $length);
      }
    }
    $result = self::unmarshall_little_endian_integer($binary, $length - 1, 1);
    return array($result, $length);
  }

  static function unmarshall_length_coded_string($binary)
  {
    list($value, $length) = self::unmarshall_length_coded_binary($binary);
    $result = substr($binary, $length, $value);
    return array($result, $length + $value);
  }
}
