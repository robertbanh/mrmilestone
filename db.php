<?php
$path = '/home/robertbanh/cron/';
if ($_SERVER['SERVER_NAME'] == 'localhost')
	$path = '';
require_once("$path"."db_cred.php");

// Simple MySQL database class.
 class database {
   private $username;
   private $password;
   public $host;
   public $db;
   public $connected;
   protected $link;
 
   // Constructor - load the mysql_cred credentials.
   function __construct() {
     $this -> username  = mysql_cred::$username;
	 $this -> password  = mysql_cred::$password;
	 $this -> host      = mysql_cred::$host;
	 $this -> db        = mysql_cred::$db;
	 $this -> connected = FALSE;
   }
   
   // Temporary dump function to print status
   function dump() {
     print ("This is a test function that should be removed in the release version of the code:<br \>");
     print ("Username: ".$this -> username."<br \>");
	 print ("Password: ");
	  if ($this -> password != "") print ("(Available)<br \>");
	  else print("(Unavailable)<br \>");
	 print ("Host: ".$this -> host."<br \>");
 	 print ("Database: ".$this -> db."<br \>");
	  if ($this -> connected) print("Connected to database.<br \>");
	  if (!$this -> connected) print ("Not connected to database.<br \>");
   }
   
   // Establish the connection to the database
   function connect() {
     $this -> link = @mysqli_connect($this -> host, $this -> username, $this -> password, $this -> db);
	  if ($this -> link) $this -> connected = TRUE;
	  else $this -> connected = FALSE;
	 return $this -> connected;
   }
   
   // Perform a query, commit, and return results in an array
   function query($query,&$output) {
     $output = array();
     $result = @mysqli_query($this -> link,$query);
	  if (!$result) return FALSE;
	  if ($result !== TRUE) {
	    while ($row = mysqli_fetch_array($result)) {
	     $output[] = $row;
	    }
	  }
	 mysqli_commit($this -> link);
	 return ($output);
   }
   
 }
?>