<?php
// ======================
// Author: Robert Banh
//
// Note: add should stripslashes to clean anything coming back from a select
// ======================
// load library
$path = '/home/robertbanh/cron/';
if ($_SERVER['SERVER_NAME'] == 'localhost')
	$path = '';
require_once("$path"."definition.php");

// class
class dbFacade extends database 
{

	// constructor
	public function __construct()
	{
		parent::__construct();
		parent::connect();
		// debug
		//parent::dump();
	}
	
	// =======================================
	// 
	// =======================================
	public function getTopNewUsers()
	{
		$result = array();
		$query = "
			select 
				screenName
			from users
			where active = 1
			order by id desc
			limit 10
			";
		$this -> query($query, $result);
		return $result;
	}
	
	// =======================================
	// 
	// =======================================
	public function getUserId($u)
	{
		$u = mysqli_real_escape_string($this->link, $u);
		
		$result = array();
		$query = "
			select 
				id
			from users
			where screenName = '$u'
			and active = '1'
			";
		$this -> query($query, $result);
		if (count($result) > 0) return $result[0][0]; // return the id
		return false;
	}
	
	// =======================================
	// 
	// =======================================
	public function addNewUser($u, $fc)
	{
		$u = mysqli_real_escape_string($this->link, $u);
		$fc = mysqli_real_escape_string($this->link, $fc);
		
		// create new user in users table
		$columns = array(
			'screenName' => $u
			);
		$this -> insertRow('users', $columns);
		// fetch the userID
		$userId = $this->getUserId($u);
		// create new user in followers table
		$columns = array(
			'user_id' => $userId,
			'total' => $fc,
			'target' => def_fetchNextTarget($fc)
			);
		$this -> insertRow('followers', $columns);
	}
	
	
	// =======================================
	// 
	// =======================================
	public function updateFollowersCount($userId, $fc, $reminder = false, $active = false, $lastTarget = false)
	{
		$userId = mysqli_real_escape_string($this->link, $userId);
		$fc = mysqli_real_escape_string($this->link, $fc);
		
		// update followers table
		$columns = array(
			'total' => $fc,
			'target' => def_fetchNextTarget($fc),
			'updateDt' => date('Y-m-d H:i:s')
			);
		// reminder updates
		if ($reminder !== false)
		{
			$reminder = mysqli_real_escape_string($this->link, $reminder);
			$columns['reminder'] = $reminder;
		}
		// active updates
		if ($active !== false)
		{
			$active = mysqli_real_escape_string($this->link, $active);
			$columns['active'] = $active;
		}
		// lastTarget updates
		if ($lastTarget !== false)
		{
			$lastTarget = mysqli_real_escape_string($this->link, $lastTarget);
			$columns['lastTarget'] = $lastTarget;
			$columns['lastTargetDt'] = date('Y-m-d H:i:s');
		}
		
		$where = "user_id='$userId' and active='1'";
		
		$this -> updateRow('followers', $columns, $where);
	}
	
	// =======================================
	// 
	// =======================================
	public function getFollowersInfo($userId)
	{
		$userId = mysqli_real_escape_string($this->link, $userId);
		
		$result = array();
		$query = "
			select
				id,
				updateDt,
				total,
				target,
				lastTarget,
				reminder
			from followers
			where user_id = '$userId'
			and active = '1'
			";
		$this -> query($query, $result);
		
		return $result;
	}
	
	// =======================================
	// 
	// =======================================
	public function deactivateUser($userId)
	{
		$userId = mysqli_real_escape_string($this->link, $userId);
		
		// update users table
		$columns = array(
			'active' => '0'
			);
		$where = "id='$userId'";
		$this -> updateRow('users', $columns, $where);
		
		// update followers table
		$columns = array(
			'reminder' => '0',
			'active' => '0'
			);
		$where = "user_id='$userId'";
		$this -> updateRow('followers', $columns, $where);
	}
	
	// =======================================
	// 
	// =======================================
	public function getStats_newUsers($dt)
	{
		$dt = mysqli_real_escape_string($this->link, $dt);
		
		$result = array();
		$query = "
			select
				u.id as user_id,
				u.screenName as screenName,
				u.createDt as createDt,
				f.updateDt as updateDt,
				u.active as uActive,
				f.total as currFollowers,
				f.target as target,
				f.lastTarget as lastTarget,
				f.reminder as reminder,
				f.active as fActive,
				f.lastTargetDt as lastTargetDt
			from users u
			inner join followers f on f.user_id = u.id
			";
		if ($dt != 'all')
			$query .= "
			where u.createDt > '$dt 00:00:00'
				and u.createDt < '$dt 23:59:59'
			";
		$this -> query($query, $result);
		return $result;
	}
	
	// =======================================
	// private
	// =======================================
	private function insertRow($table, $columns)
	{
		$keys = array_keys($columns);
		$query_cols = '';
		$query_vals = '';
		
		// safety precaution
		foreach ($keys as $key)
		{
			$columns[$key] = mysqli_real_escape_string($this -> link, $columns[$key]);
			$query_cols .= "$key, ";
			$query_vals .= "'" . $columns[$key] . "', ";
		}
		
		// remove the last comma
		$query_cols = preg_replace('/, $/', '', $query_cols);
		$query_vals = preg_replace('/, $/', '', $query_vals);
		
		$query = "
			INSERT INTO $table
				( $query_cols )
			VALUES
				( $query_vals )
		";
		
		$result = '';
		$this -> query($query, $result);
		//echo $query; die;
	}
	
	// =======================================
	// private
	// =======================================
	private function updateRow($table, $columns, $where)
	{
		$keys = array_keys($columns);
		$set = '';
		
		// safety precaution
		foreach ($keys as $key)
		{
			$columns[$key] = mysqli_real_escape_string($this -> link, $columns[$key]);
			$set .= "$key = '" . trim($columns[$key]) . "', ";
		}
		
		// remove the last comma
		$set = preg_replace('/, $/', '', $set);
		
		$query = "
			UPDATE $table
			SET
				$set 
			WHERE
				$where
		";
		//die($query);
		$result = '';
		$this -> query($query, $result);
		//echo $query; die;
	}
	
}
?>