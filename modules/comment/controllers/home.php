<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Comment_Home_module extends CI_Module {

	/**
	 * 构造函数
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{
		parent::__construct();
	}

	function index($data1, $data2)
    {
		$this->load->model('Data_model');
		$this->Data_model->start();

		$this->load->view('view_test');
	}
}
