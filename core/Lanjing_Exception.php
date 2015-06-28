<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * 
 * 
 */

class Lanjing_Exception extends Exception
{
    /**
     * @param string $strMsg 出错信息
     * @param string $intErrorCode 错误编码
     */
	public function __construct($strMsg, $intErrorCode)
	{
		parent::__construct($strMsg, $intErrorCode);
	}

}

/* End of file Lanjing_Exception.php */
/* Location: ./application/core/Lanjing_Exception.php */
