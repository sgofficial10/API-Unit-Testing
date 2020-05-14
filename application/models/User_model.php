<?php


defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_model {


    public function insertData($insert_array, $table) {
        $this->db->insert($table, $insert_array);
        return  $this->db->insert_id();
    }



    public function checkDataExistance($condition_array, $table) {
        $query = $this->db->select('email_address, user_id, password, user_name')
                        ->where($condition_array)
                        ->get($table);
        if($query->num_rows() > 0) {
            return $query->row_array();
        } else {
           return []; 
        }
    }



    public function get_search_data($search_data, $offset, $limit, $table, $user_id) {
        $result = array();
        $query = $this->db->query(" SELECT user_name, user_id, email_address FROM ".$table." WHERE MATCH(user_name) AGAINST('*".$search_data."*' IN BOOLEAN MODE) AND user_id <> ".$user_id." AND is_delete = 0 LIMIT ".$offset.", ".$limit." ");
        if($query->num_rows() > 0) {
            foreach($query->result_array() as $key => $value) {
                $friend_status_query = $this->db->query("SELECT user_id, relative_id, status FROM user_relation WHERE (user_id = ".$user_id." AND relative_id = ".$value['user_id']." ) OR (user_id = ".$value['user_id']." AND relative_id = ".$user_id.") ");
                if($friend_status_query->num_rows() > 0) {
                    $friend_status = $friend_status_query->row_array();
                    if($friend_status['status'] === 'S') {
                        if((int)$user_id === (int)$friend_status['user_id']){
                            $value['is_friend'] = 'Send';
                        } else {
                            $value['is_friend'] = 'Accept';
                        }
                        
                    } else {
                        $value['is_friend'] = 'Yes';
                    }
                } else {
                    $value['is_friend'] = 'No';
                }
                $result[] = $value;
            }
            return $result;
        } else {
           return []; 
        }
    }


    public function get_mutual_connection($user_id, $table) {
        $query = $this->db->query("SELECT relative_id, user_id FROM ".$table." WHERE (user_id = ".$user_id." AND status = 'A' ) OR (relative_id = ".$user_id." AND status = 'A') ");
        if($query->num_rows() > 0) {
            foreach($query->result_array() as $key => $value) {
                if($value['user_id'] == $user_id) {
                    $userIdArray[] = $value['relative_id'];
                } else {
                    $userIdArray[] = $value['user_id'];
                }   
            }
            return $userIdArray;
        } else {
           return [];
        }
    }



    
    

    public function send_request($user_id, $relative_id, $table) {
        $query = $this->db->query("SELECT relation_id, status FROM ".$table." WHERE (user_id = ".$user_id." AND relative_id = ".$relative_id." ) OR (user_id = ".$relative_id." AND relative_id = ".$user_id.") ");
        if($query->num_rows() > 0) {
            $result = $query->row_array();
            if($result['status'] === 'S') {
                return 'Already Sent.';
            } elseif($result['status'] === 'A') {
                return 'Already Friend.';
            }
            return TRUE;
        } else {
            $insert_array = array(
                'user_id' => $user_id,
                'relative_id' => $relative_id
            );
            $status = $this->insertData($insert_array, $table);
            if(!empty($status)) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }



    public function accept_request($user_id, $relative_id, $table) {
        $query = $this->db->query("SELECT relation_id, status FROM ".$table." WHERE (user_id = ".$user_id." AND relative_id = ".$relative_id." ) OR (user_id = ".$relative_id." AND relative_id = ".$user_id.") ");
        if($query->num_rows() > 0) {
            $result = $query->row_array();
            if($result['status'] === 'S') {
                $this->db->query("UPDATE ".$table." SET status = 'A' WHERE relation_id = ".$result['relation_id']." ");
            } elseif($result['status'] === 'A') {
                return 'Already Friend.';
            }
            return TRUE;
        } else {
            return "Send request first";
        }
    }

}