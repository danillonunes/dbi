<?php
/**
 * DBI: the simple database interactive manager.
 */

function main() {
  if (isset($_SERVER["argv"][1])) {
    $commands = command_list();
    if (isset($commands[$_SERVER["argv"][1]])) {
      $commands[$_SERVER["argv"][1]]['callback']();
    }
  }
  else {
    command_interactive();
  }
}

function command_list() {
  return array(
    'create' => array(
      'description' => 'Create a new database.',
      'callback' => 'db_create'
    ),
    'delete' => array(
      'description' => 'Delete a given database.',
      'callback' => 'db_delete'
    )
  );
}

function command_interactive() {
  $commands = command_list();

  $indexed_commands = array_merge(array('zero'), array_keys($commands));

  echo 'What do you want to do?';
  echo "\n\n";

  foreach ($indexed_commands as $i => $command) {
    if (isset($commands[$command])) {
      echo "$i) " . $commands[$command]['description'];
      echo "\n";
    }
  }

  echo ++$i . ") Exit";
  echo "\n";

  $command_number = prompt(':');

  if (isset($indexed_commands[$command_number]) && isset($commands[$indexed_commands[$command_number]])) {
    $commands[$indexed_commands[$command_number]]['callback']();
  }
  elseif ($command_number == $i || $command_number == 'q') {
    exit;
  }
  else {
    echo "Error: Unrecognizable command.";
    echo "\n";
    command_interactive();
  }
}

function db_create() {
  $connection = db_connect('root');

  $name = get_name();

  $select = mysql_select_db($name, $connection);

  if (!$select) {
    mysql_query("CREATE DATABASE IF NOT EXISTS  `$name`;");
    echo "Created a new database $name.";
    echo "\n";

    mysql_query("CREATE USER '$name'@'%' IDENTIFIED BY  '$name';");
    echo "Created a new user $name with the password $name.";
    echo "\n";

    mysql_query("GRANT ALL PRIVILEGES ON  `$name` . * TO  '$name'@'%' WITH GRANT OPTION;");
    echo "The user $name has granted with all privileges to database $name.";
    echo "\n";

    mysql_query("GRANT ALL PRIVILEGES ON  `$name\_%` . * TO  '$name'@'%' WITH GRANT OPTION;");
    echo "The user $name has granted with all privileges to database wildcard {$name}_*.";
    echo "You can create multiple databases with this pattern like {$name}_2 or {$name}_backup.";
    echo "\n";

    mysql_close($connection);
  }
  else {
    echo "Database $name already exists. Try another name.";
    echo "\n";
    db_create();
  }
}

function db_delete() {
  $connection = db_connect('root');

  $name = get_name();

  $select = mysql_select_db($name, $connection);

  if ($select) {
    mysql_query("DROP DATABASE `$name`;");
    echo "Deleted the database $name.";
    echo "\n";

    mysql_query("DROP USER '$name'@'%';");
    echo "Deleted the user $name.";
    echo "\n";

    mysql_close($connection);
  }
  else {
    echo "Database $name not found. Try another name.";
    echo "\n";
    db_create();
  }
}

function get_name() {
  if (isset($_SERVER["argv"][2])) {
    $name = $_SERVER["argv"][2];
  }
  else {
    $name = prompt('Enter a MySQL name to be used as MySQL user and database: ');
  }

  return $name;
}

function db_connect($user = FALSE) {
  static $connection;

  if ($connection) {
    return $connection;
  }

  if ($user == 'root') {
    echo 'We will try to connect to MySQL through root user.';
  }
  if (!$user) {
    $user = prompt('Enter a MySQL user with user and database create grants: ');
  }

  echo "\n";
  $pass = prompt_silent("Enter password for $user: ");

  if ($connection = @mysql_connect('localhost', $user, $pass)) {
    echo "Connect to MySQL through the user $user";
    echo "\n";
    return $connection;
  }
  else {
    echo "\n";
    echo 'Could not connect to MySQL with provided credentials.';
    echo "\n";
    return db_connect();
  }
}

function prompt($message) {
  echo $message;
  $handle = fopen('php://stdin', 'r');
  $reply = fgets($handle);
  echo "\n";
  return trim($reply);
}

function prompt_silent($message) {
  echo $message;
  system('stty -echo');
  $reply = fgets(STDIN);
  system('stty echo');
  echo "\n";
  return trim($reply);
}

main();
