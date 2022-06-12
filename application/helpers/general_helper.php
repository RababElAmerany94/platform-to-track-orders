<?php

defined('BASEPATH') or exit('No direct script access allowed');
header('Content-Type: text/html; charset=utf-8');

/**
 * Is user logged in
 * @return boolean
 */
function is_user_logged_in()
{
	$CI =& get_instance();
	$userdata = $CI->session->userdata();
	if (isset($userdata) && isset($userdata['logged_in']) && $userdata['logged_in'] == true) {
		return true;
	}
	return false;
}

function get_permissions($permission)
{
	$CI =& get_instance();
	$userdata = $CI->session->userdata();

	if (isset($userdata) && is_user_logged_in()) {
		return $userdata[$permission];
	} else {
		redirect('login');
	}
}

function can_list($className)
{
	$className = ucfirst(strtolower(str_replace(" ", "_", $className)));
	$classes = get_permissions("can_list");
	return in_array($className, $classes);
}

function can_read($className)
{
	$className = ucfirst(strtolower(str_replace(" ", "_", $className)));
	$classes = get_permissions("can_read");
	return in_array($className, $classes);
}

function can_add($className)
{
	$className = ucfirst(strtolower(str_replace(" ", "_", $className)));
	$classes = get_permissions("can_add");
	return in_array($className, $classes);
}

function can_edit($className)
{
	$className = ucfirst(strtolower(str_replace(" ", "_", $className)));
	$classes = get_permissions("can_edit");
	return in_array($className, $classes);
}

function can_delete($className)
{
	$className = ucfirst(strtolower(str_replace(" ", "_", $className)));
	$classes = get_permissions("can_delete");
	return in_array($className, $classes);
}

function canonize_class(&$item, $key)
{
	$item = ucfirst(strtolower(str_replace(" ", "_", $item))); // Engagement Importation => Engagement_importation
}

function pwd_hash($password = '', $algo = 'md5', $repeat = 1)
{
	$algos = ['md5' => 'e86cebe1', 'sha1' => '3332102a', 'sha256' => '5cc814f7', 'sha384' => '6aa61a1', 'sha512' => '3a86036f'];
	
	// more algos: 'md2', 'md4', 'md5', 'sha1', 'sha256', 'sha384', 'sha512', 'ripemd128', 'ripemd160', 'ripemd256', 'ripemd320', 'whirlpool', 'tiger128,3', 'tiger160,3', 'tiger192,3', 'tiger128,4', 'tiger160,4', 'tiger192,4', 'snefru', 'gost', 'adler32', 'crc32', 'crc32b', 'haval128,3', 'haval160,3', 'haval192,3', 'haval224,3', 'haval256,3', 'haval128,4', 'haval160,4', 'haval192,4', 'haval224,4', 'haval256,4', 'haval128,5', 'haval160,5', 'haval192,5', 'haval224,5', 'haval256,5'
	if (in_array($algo, array_keys($algos))) {
		for ($i = 0; $i < $repeat; $i++) {
			$password = hash($algo, $password);
		}
	}
	
	return $password . '|' . $repeat . '|' . $algos[$algo];
}

function pwd_verify($password = '', $password_hashed = '')
{
	$algos = ['md5' => 'e86cebe1', 'sha1' => '3332102a', 'sha256' => '5cc814f7', 'sha384' => '6aa61a1', 'sha512' => '3a86036f'];
	$items = explode("|", $password_hashed);
	
	return ($password_hashed == pwd_hash($password, array_search($items[2], $algos), $items[1]));
}

function settings($key)
{
	$CI =& get_instance();
	$setting = $CI->db->get_where("settings","key='$key'")->result();
	if($setting[0]) 
		return $setting[0]->value;
	else 
		return false;
}

function array_values_recursive($ary)
{
   $lst = array();
   foreach( array_keys($ary) as $k ){
      $v = $ary[$k];
      if (is_scalar($v)) {
         $lst[] = $v;
      } elseif (is_array($v)) {
         $lst = array_merge( $lst,
            array_values_recursive($v)
         );
      }
   }
   return $lst;
}


