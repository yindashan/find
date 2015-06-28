<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Community_data_model extends CI_Model {

    private $table_name = 'lj_tweet';
	function __construct()
	{
		parent::__construct();
	}

    /**
     * 发表帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function add($request) {

        $data = array(
            'uid' => $request['uid'],
            'catalog' => $request['catalog'],    
            'title' => $request['title'],    
            'content' => $request['content'],    
            'ctime' => $request['ctime'],    
            'parent_tid' => $request['parent_tid'],    
            'origin_tid' => $request['origin_tid'],    
            'is_del' => $request['is_del'],    
            'is_essence' => $request['is_essence'],    
            'dtime' => $request['dtime'],    
            'img' => $request['img'],    
        );
        $result = $this->db->insert($this->table_name, $request);
        return $result;
    }

    /**
     * 获取帖子列表
     *
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list($limit, $offset) {

        $result = $this->db->get($this->table_name, $limit, $offset);
        return $result;
    }

    /**
     * 根据帖子id获取帖子列表，上拉刷新
     *
     * @param string tid 帖子id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list_by_tid($tid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('tid >', $tid);
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        return $result;
    }

    /**
     * 获取用户帖子列表
     * 
     * @param string uid 用户id
     * @param int limit 每页显示条数
     * @param int offset 偏移量
     * @return array 帖子列表
     */
    function get_list_by_uid($uid, $limit, $offset) {
    
        $this->db->select('*');
        $this->db->where('uid', $uid);
        $this->db->limit($limit, $offset);

        $result = $this->db->get($this->table_name); 
        return $result;
    }

    /**
     * 根据帖子id获取帖子详情
     *
     * @param int tid 帖子id
     * @return array 帖子详情
     */
    function get_detail_by_tid($tid) {
    
        //$query = $this->db->get($this->table_name);
        //print_r($query);exit;
        $this->db->select('*');
        $this->db->from($this->table_name);
        $this->db->where('tid', intval($tid));

        $result = $this->db->get();
        //$result = $this->db->get($this->table_name); 
        //$str = $this->db->last_query();
        //$result = $this->db->query("SELECT * FROM `lj_tweet` WHERE `tid` = 1");
        //echo $str;exit;
        //print_r($result);exit;
        return $result;
    }

    /**
     * 根据帖子id删除帖子
     *
     * @param string tid 帖子id
     * @return bool 状态
     */
    function remove_by_tid($tid) {
    
        $this->db->where('tid', $tid);
        $result = $this->db->delete($this->table_name); 
        return $result;
    }

    /**
     * 根据用户id删除帖子
     *
     * @param string 用户id
     * @return bool 状态
     */
    function remove_by_uid($uid) {
    
        $this->db->where('uid', $uid);
        $result = $this->db->delete($this->table_name); 
        return $result;
    }

    /**
     * 更新帖子
     *
     * @param array 请求参数
     * @return bool 状态
     */
    function update_by_tid($request) {
        $tid = $request['tid'];
        $data = array();

        $this->db->where('tid', $tid);
        $result = $this->db->update($this->table_name, $data); 
        return $result;
    }
}
