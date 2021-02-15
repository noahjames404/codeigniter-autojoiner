<?php

namespace App\Controllers;
use App\Models;
/*
  @author Noah James C. Yanga

	THIS IS ONLY AN EXAMPLE
*/
class Home extends BaseController
{
	public function index()
	{
		$res = $inventory_log_model->getList(0,10,"Plain");
		echo "<pre>";
		print_r($res);
		echo "</pre>";
	}
}
