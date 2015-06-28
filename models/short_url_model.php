<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Short_url_model extends CI_Model {
    private $table_name = 'ci_short_url';
    private $letter = array('0', '1', '2', '3', '4', '5', '6',
                            '7', '8', '9', 'a', 'b', 'c', 'd',
                            'e', 'f', 'g', 'h', 'i', 'j', 'k',
                            'l', 'm', 'n', 'o', 'p', 'q', 'r',
                            's', 't', 'u', 'v', 'w', 'x', 'y',
                            'z', 'A', 'B', 'C', 'D', 'E', 'F',
                            'G', 'H', 'I', 'J', 'K', 'L', 'M',
                            'N', 'O', 'P', 'Q', 'R', 'S', 'T',
                            'U', 'V', 'W', 'X', 'Y', 'Z' 
                       );
    private $size = 6;

    private $base = 62;
    private $base_url = 'http://123.57.249.33/s/';

	function __construct() {
		parent::__construct();
	}

    /*turn a integer to a BASE number list
     */
    private function dehydrate($integer) {
        $result = array();
        while ($integer > 0) {
            array_push($result, $integer % $this->base);
            $integer = intval($integer / $this->base);
        }

        $result = array_reverse($result);
        $c = count($result);
        for ($i = $c; $i < $this->size; $i++) {
            array_push($result, 0);
        }

        return $result;
    }
    function generate_url($url) {
        $crc = crc32($url);
        $encrypt = $this->dehydrate($crc);
        $tinyurl = array();
        foreach($encrypt as $i) {
            array_push($tinyurl, $this->letter[$i]);
        }
        $tinyurl = implode($tinyurl);

        $sql = "INSERT INTO ".$this->table_name." (`url`, `tinyurl`) values (?, ?) ON DUPLICATE KEY UPDATE `url`=?";
        $ret = $this->db->query($sql, array($url, $tinyurl, $url));
        if ($ret) {
            return $this->base_url.$tinyurl;
        }

        return '';
    }

    function get_origin_url($tinyurl) {
        $this->db->select('url');
        $this->db->from($this->table_name);
        $this->db->where('tinyurl', $tinyurl);

        $query = $this->db->get();
        if (!empty($query) && !empty($query->result_array())) {
            return $query->result_array()[0]['url'];
        }

        return '';
    }

}
