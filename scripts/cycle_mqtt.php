<?php
chdir(dirname(__FILE__) . '/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");

set_time_limit(0);

require("./lib/mqtt/phpMQTT.php");
include_once(DIR_MODULES . "mqtt/mqtt.class.php");

$mqtt = new mqtt();
$mqtt->getConfig();

if ($mqtt->config['MQTT_AUTH'])
{
   $username = $mqtt->config['MQTT_USERNAME'];
   $password = $mqtt->config['MQTT_PASSWORD'];
}

$host = 'localhost';

if ($mqtt->config['MQTT_HOST'])
{
   $host = $mqtt->config['MQTT_HOST'];
}

if ($mqtt->config['MQTT_PORT'])
{
   $port = $mqtt->config['MQTT_PORT'];
}
else
{
   $port = 1883;
}

if ($mqtt->config['MQTT_QUERY'])
{
   $query = $mqtt->config['MQTT_QUERY'];
}
else
{
   $query = '/var/now/#';
}

$mqtt_client = new phpMQTT($host, $port, "MajorDoMo MQTT Client");

if ($mqtt->config['MQTT_AUTH'])
{
  if (!$mqtt_client->connect(true, NULL, $username, $password)) {
    exit(1);
  }
} else {
  if (!$mqtt_client->connect())
  {
    exit(1);
  }
}

$topics[$query] = array("qos" => 0, "function" => "procmsg");
$mqtt_client->subscribe($topics, 0);
$previousMillis = 0;

while ($mqtt_client->proc())
{
   $currentMillis = round(microtime(true) * 10000);
   
   if ($currentMillis - $previousMillis > 10000)
   {
      $previousMillis = $currentMillis;
   
      setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
  
      if (file_exists('./reboot') || IsSet($_GET['onetime']))
      {
         $db->Disconnect();
         exit;
      }
   }
}

$mqtt_client->close();

/**
 * Process message
 * @param mixed $topic Topic
 * @param mixed $msg   Message
 * @return void
 */
function procmsg($topic, $msg)
{
   global $mqtt;
   $mqtt->processMessage($topic, $msg);
   echo date("Y-m-d H:i:s") . " Topic:{$topic} $msg\n";
}

 $db->Disconnect(); // closing database connection
