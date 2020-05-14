<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH."/libraries/REST_Controller.php";


/**
 * @author: Sujay Ghosh(ghoshsujay95@gmail.com)
 */

class SocialUser extends REST_Controller {


    public function __construct() {
        parent:: __construct();
        $this->load->model('User_model');
        $this->load->helper(['jwt', 'authorization']); 
    }


    /**
     * @purpose : Registration of user
     * @table : social_users
     * @api_path : http://localhost/social/index.php/SocialUser/user_registration
     */
    public function user_registration_post() {
        try {
            $data                                   =   $this->_post_args;
            $user_name                              =   array_key_exists("user_name", $data) ? $data["user_name"] : NULL;
            $email_address                          =   array_key_exists("email_address", $data) ? $data["email_address"] : NULL;
            $password                               =   array_key_exists("password", $data) ? $data["password"] : NULL;
            $device_type                            =   array_key_exists("device_type", $data) ? $data["device_type"] : NULL;
            if(is_null($user_name) || trim($user_name) === "" || empty($user_name) || is_null($email_address) || trim($email_address) === "" || empty($email_address) || is_null($password) || trim($password) === "" || empty($password) || is_null($device_type) || trim($device_type) === "" || empty($device_type)) {
                $this->response(array("respCode" => -1, 'error' => "user_name, email_address, password and device_type are required!"), 400);
            } else {
                if($device_type === 'android' || $device_type === 'web' || $device_type === "ios") {
                    if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                        $condition_array                =   array('email_address' => trim(strip_tags($email_address)), 'is_delete' => 0);
                        $check_email_exists             =   $this->User_model->checkDataExistance($condition_array, 'social_users');
                        if(!empty($check_email_exists)) {
                            $this->response(array("respCode" => -1, 'error' => "Email address already exists"), 400);
                        } else {
                            $hashed_password = sha1(trim($password));
                            $insert_array = array(
                                'user_name'             =>  trim(strip_tags($user_name)),
                                'email_address'         =>  trim(strip_tags($email_address)),
                                'password'              =>  $hashed_password,
                                'device_type'           =>  $device_type
                            );
                            $inserted_id                =   $this->User_model->insertData($insert_array, 'social_users');
                            if(!empty($inserted_id)) {
                                $this->response(array("respCode" => 1, 'success' => "User registration sucessfully done."), 200);
                            } else {
                                $this->response(array("respCode" => -1, 'error' => "User registartion failed."), 400); 
                            }
                        }
                    } else {
                        $this->response(array("respCode" => -1, 'error' => "Invalid email address!"), 400);
                    }
                } else {
                    $this->response(array("respCode" => -1, 'error' => "Invalid deviceType!"), 400);
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }



    /**
     * @purpose:  login into application
     * @table: social_users
     * @api_path: http://localhost/social/index.php/SocialUser/login
     */
    public function login_post() {
        try {
            $data                                   =   $this->_post_args;
            $email_address                          =   array_key_exists("email_address", $data) ? $data["email_address"] : NULL;
            $password                               =   array_key_exists("password", $data) ? $data["password"] : NULL;
            if( is_null($email_address) || trim($email_address) === "" || empty($email_address) || is_null($password) || trim($password) === "" || empty($password)) {
                $this->response(array("respCode" => -1, 'error' => "email_address, password is required!"), 400);
            } else {
                if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                    $condition_array                =   array('email_address' => trim(strip_tags($email_address)), 'is_delete' => 0);
                    $get_user_details               =   $this->User_model->checkDataExistance($condition_array, 'social_users');
                    if(!empty($get_user_details)) {
                        $hashed_password = sha1(trim($password));
                        if($hashed_password === $get_user_details['password']) {
                            $tokenData              =   array(
                                'email_address'     =>  $get_user_details['email_address'],
                                'user_id'           =>  $get_user_details['user_id'] 
                            ); 
                            $token = AUTHORIZATION::generateToken($tokenData);
                            $this->response(array("respCode" => 1, 'success' => "Login successfully done.", "data" => array('user_id' => $get_user_details['user_id'], 'token' => $token)), 200);
                        } else {
                            $this->response(array("respCode" => -1, 'error' => "Password does not match!"), 400);
                        }
                    } else {
                        $this->response(array("respCode" => -1, 'error' => "Email address not found! Register Now."), 400);
                    }
                } else {
                    $this->response(array("respCode" => -1, 'error' => "Invalid email address!"), 400);
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }


    /**
     * @purpose : searches user 
     * @table : social_users, user_relation
     * @api_path : http://localhost/social/index.php/SocialUser/search_user
     */
    public function search_user_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            $search_text                            =   array_key_exists("search_text", $data) ? $data["search_text"] : NULL;
            $offset                                 =   array_key_exists("offset", $data) ? (int)$data["offset"] : 0;
            if(empty($search_text) || is_null($search_text) || trim($search_text) === '' || is_null($user_id) || empty($user_id) || trim($user_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id, search_text are required!"), 400);
            } else {
                try {
                    $data                           =   AUTHORIZATION::validateToken($jwtHeaderToken);
                    if($data !== FALSE) {
                        if($data->{'user_id'} === $user_id) {
                            $get_search_data        =   $this->User_model->get_search_data($search_text, $offset, 10, 'social_users', $user_id);
                            $offset                 =   ($offset === 0 ? $offset+ 10 + 1 : $offset + 10);
                            $this->response(array("respCode" => 1, "data" => $get_search_data, 'offset' => $offset), 200);
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                        }
                    } else {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                } catch(Exception $error) {
                    $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }



    /**
     * @purpose: get new token 
     * @api_path : http://localhost/social/index.php/SocialUser/validate_token
     */
    public function validate_token_post() {
        try {
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            if(empty($user_id) || is_null($user_id) || trim($user_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id is required!"), 400);
            } else {
                    $condition_array = array('user_id' => $user_id, 'is_delete' => 0);
                    $get_user_details = $this->User_model->checkDataExistance($condition_array, 'social_users');
                    if(!empty($get_user_details)) {
                        $tokenData              =   array(
                            'email_address'     =>  $get_user_details['email_address'],
                            'user_id'           =>  $get_user_details['user_id'] 
                        ); 
                        $token                  =   AUTHORIZATION::generateToken($tokenData);
                        $this->response(array("respCode" => 1, 'success' => "Token Generated sucessfully", "token" => $token), 200);
                    } else {
                        $this->response(array("respCode" => -1, "error" => "Invalid user_id."), 400);
                    }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }


    /**
     * @purpose: send friend request
     * @table: user_relation
     * @api_path: http://localhost/social/index.php/SocialUser/send_request
     */
    public function send_request_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            $relative_id                            =   array_key_exists("relative_id", $data) ? $data["relative_id"] : NULL;
            if(empty($relative_id) || is_null($relative_id) || trim($relative_id) === '' || is_null($user_id) || empty($user_id) || trim($user_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id, search_text are required!"), 400);
            } else {
                if($user_id === $relative_id) {
                    $this->response(array("respCode" => -1, "error" => "Both id can not be same."), 400);
                } else {
                    try {
                        $data                           =   AUTHORIZATION::validateToken($jwtHeaderToken);
                        if($data !== FALSE) {
                            if($data->{'user_id'} === $user_id) {
                                $send_request_status    =   $this->User_model->send_request($user_id, $relative_id, 'user_relation');
                                $this->response(array("respCode" => 1, "status" => $send_request_status), 200);
                            } else {
                                $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                            }
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                        }
                    } catch(Exception $error) {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }


    /**
     * @purpose: send friend request
     * @table: user_relation
     * @api_path: http://localhost/social/index.php/SocialUser/accept_request
     */
    public function accept_request_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            $relative_id                            =   array_key_exists("relative_id", $data) ? $data["relative_id"] : NULL;
            if(empty($relative_id) || is_null($relative_id) || trim($relative_id) === '' || is_null($user_id) || empty($user_id) || trim($user_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id, search_text are required!"), 400);
            } else {
                if($user_id === $relative_id) {
                    $this->response(array("respCode" => -1, "error" => "Both id can not be same."), 400);
                } else {
                    try {
                        $data                               =   AUTHORIZATION::validateToken($jwtHeaderToken);
                        if($data !== FALSE) {
                            if($data->{'user_id'} === $user_id) {
                                $accept_request_status      =   $this->User_model->accept_request($user_id, $relative_id, 'user_relation');
                                $this->response(array("respCode" => 1, "status" => $accept_request_status), 200);
                            } else {
                                $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                            }
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                        }
                    } catch(Exception $error) {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }



    /**
     * @purpose: fetch users details
     * @table: social_users
     * @api_path: http://localhost/social/index.php/SocialUser/fetch_profile
     */
    public function fetch_profile_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            $relative_id                            =   array_key_exists("relative_id", $data) ? $data["relative_id"] : NULL;
            if( is_null($user_id) || empty($user_id) || trim($user_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id is required!"), 400);
            } else {
                try {
                    $data                               =   AUTHORIZATION::validateToken($jwtHeaderToken);
                    if($data !== FALSE) {
                        if($data->{'user_id'} === $user_id) {
                            $condition_array            =   array('user_id' => (is_null($relative_id) ? $user_id : $relative_id), 'is_delete' => 0);
                            $get_user_details           =   $this->User_model->checkDataExistance($condition_array, 'social_users');
                            unset($get_user_details['password']);
                            $this->response(array("respCode" => 1, 'details' => $get_user_details), 200);
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                        }
                    } else {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                } catch(Exception $error) {
                    $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }


    /**
     * @purpose: get mutual connection
     * @table: user_relation
     * @api_path: http://localhost/social/index.php/SocialUser/mutual_connection
     */
    public function mutual_connection_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            $relative_id                            =   array_key_exists("relative_id", $data) ? $data["relative_id"] : NULL;
            $offset                                 =   array_key_exists("offset", $data) ? $data["offset"] : 0;
            if( is_null($user_id) || empty($user_id) || trim($user_id) === '' || is_null($relative_id) || empty($relative_id) || trim($relative_id) === '') {
                $this->response(array("respCode" => -1, 'error' => "user_id, relative_id are required!"), 400);
            } else {
                if($user_id === $relative_id) {
                    $this->response(array("respCode" => -1, "error" => "Both id can not be same."), 400);
                } else {
                    try {
                        $data                                   =   AUTHORIZATION::validateToken($jwtHeaderToken);
                        if($data !== FALSE) {
                            if($data->{'user_id'} === $user_id) {
                                $details_array                  =   array();
                                $mutual_array                   =   array_intersect($this->User_model->get_mutual_connection($user_id, 'user_relation'), $this->User_model->get_mutual_connection($relative_id, 'user_relation'));
                                foreach(array_slice($mutual_array, $offset, 10) as $key => $value) {
                                    $condition_array = array('user_id' => $value, 'is_delete' => 0);
                                    $get_user_details           =   $this->User_model->checkDataExistance($condition_array, 'social_users');
                                    unset($get_user_details['password']);
                                    $details_array[]            =   $get_user_details;
                                }
                                $offset = $offset + 10;
                                $this->response(array("respCode" => 1, 'list' => $details_array, 'offset' => $offset), 200);
                            } else {
                                $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                            }
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                        }
                    } catch(Exception $error) {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }


    /**
     * @purpose: logout api 
     * @api_path: http://localhost/social/index.php/SocialUser/logout
     */
    public function logout_post() {
        try {
            $jwtHeaderToken = $this->input->get_request_header('Token');
            if (is_null($jwtHeaderToken) || empty($jwtHeaderToken) || trim($jwtHeaderToken) === '') { 
                $this->response(array("respCode" => -1, "error" => "Token is required."), 400);
            }
            $data                                   =   $this->_post_args;
            $user_id                                =   array_key_exists("user_id", $data) ? $data["user_id"] : NULL;
            if( is_null($user_id) || empty($user_id) || trim($user_id) === '' ) {
                $this->response(array("respCode" => -1, 'error' => "user_id is required!"), 400);
            } else {
                try {
                    $data                                   =   AUTHORIZATION::validateToken($jwtHeaderToken);
                    if($data !== FALSE) {
                        if($data->{'user_id'} === $user_id) {
                            $this->response(array("respCode" => 1, "success" => "Logout successfully done."), 200);
                        } else {
                            $this->response(array("respCode" => -1, "error" => "Invalid request."), 400);
                        }
                    } else {
                        $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                    }
                } catch(Exception $error) {
                    $this->response(array("respCode" => -1, "error" => "Invalid token."), 400);
                }
            }
        } catch(Exception $e) {
            $this->response(array("respCode" => -1, 'error' => $e->getMessage()), 500);
        }
    }





}