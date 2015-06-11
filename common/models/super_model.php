<?php

class Super_model extends CI_Model
{


    public function __construct()
    {
        $this->load->database();
    }

    /**
     * @新增后台管理员权限
     */
    public function insert_right($data)
    {
        $this->db->insert('bi_right', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }

    /**
     * @修改后台管理员权限
     * @param array $where
     * @param array $data
     */
    public function modify_right($data, $where)
    {
        return $this->db->update('bi_right', $data, $where);
    }

    /**
     * @author zhoushuai
     * @根据角色。获取权限列表
     */
    public function get_right_list($where = '')
    {
        $this->db->select(' r.*,p.id_profile_right,p.id_profile')->from('bi_right as r')->
            join('bi_profile_right as p', ' r.id_right = p.id_right', 'left')->order_by('id_parent ASC,orders ASC');
        if (!empty($where)) {
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        //return $this->db->last_query();
        return $result;
    }

    /**
     * @author zhoushuai
     * @获取权限列表
     */
    public function get_right($where)
    {
        $this->db->select('*')->from('bi_right AS r')->where($where);
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }

    /**
     * @author zhoushuai
     * @获取权限信息
     */
    public function get_right_by_id($id_right)
    {
        $this->db->select('*')->from('bi_right AS r ')->where(array('r.id_right' => $id_right));
        $result = $this->db->get()->result_array();
        return $result[0];
    }

    /**
     * @author zhoushuai
     * @获取管理员角色
     */
    public function get_profile($where = '')
    {
        $this->db->select('*')->from('bi_profile');
        if (!empty($where)) {
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }

    /**
     * @添加管理员角色
     * @param data 新关注的数据
     * @param 返回插入数据的id
     */
    public function add_profile($data)
    {
        $this->db->insert('bi_profile', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    /**
     * @删除管理员角色
     */
    public function delete_profile($where)
    {
        return $this->db->delete('bi_profile', $where);
        //$this->db->last_query();
    }

    /**
     *@增加管理员角色权限
     */
    public function insert_profile_right($data)
    {
        return $this->db->insert_batch("bi_profile_right", $data);
    }

    public function insert_pright($data)
    {
        $this->db->insert('bi_profile_right', $data);
        $id = $this->db->insert_id();
        return $id;
    }

    /**
     *@删除管理员角色权限
     */
    public function delete_profile_right($where)
    {
        return $this->db->delete("bi_profile_right", $where);
    }


    /**
     * @数据库操作
     * @查询所有数据库
     */
    public function show_list_databases()
    {
        $this->load->dbutil();
        $dbs = $this->dbutil->list_databases();
        return $dbs;
    }

    /**
     * @查询数据库所有表
     */
    public function show_list_tables()
    {
        $query = $this->db->query('SHOW Tables');
        $tables = array();
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $tables[] = current($row);
            }
        }
        return $tables;
    }

    /**
     * @执行SQL语句
     * @INSERT、DELETE、UPDATE、SELECT、ALTER
     */
    public function exec($mysql)
    {
        $extype = trim(strtoupper(substr(trim($mysql), 0, 6)));
        switch ($extype) {
            case 'SELECT':
                return $this->exec_select($mysql);
                break;
            case 'DELETE':
            case 'UPDATE':
                $this->db->query($mysql);
                return $this->db->affected_rows();
                break;
            case 'INSERT':
                $this->db->query($mysql);
                $id = $this->db->insert_id();
                return $id;
                break;
            case 'ALTER':
                return $this->db->query($mysql);
                break;
            default:
                return false;
                break;
        }
    }


    private function exec_select($mysql)
    {
        $query = $this->db->query($mysql);
        $field = '<tr>';
        foreach ($query->list_fields() as $key) {
            $field .= "<td>{$key}</td>";
        }
        $field .= '</tr>';
        $data = array();
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data[] = $row;
            }
        }
        return $this->_print($data, $field);
    }


    private function _print($data, $field)
    {
        $html = '<table style="text-align:center;" border="1px" cellpadding="0" cellspacing = "0">';
        $html .= $field;
        while (list($key, $val) = each($data)) {
            $html .= '<tr>';
            while (list($key1, $val1) = each($val)) {
                $html .= "<td>{$val1}</td>";

            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }


}
