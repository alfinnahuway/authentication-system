<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index()
    {

        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Login Page';
            $this->load->view('templates/auth-header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/auth-footer');
        } else {
            //validasinya success
            $this->_login();
        }
    }

    private function _login()
    {

        $email = $this->input->post('email');
        $password = $this->input->post('password');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            // usernya ada
            if ($user['is_active'] == 1) {
                //cek passwordnya
                if (password_verify($password, $user['password'])) {
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    $this->session->set_userdata($data);
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                } else {
                    $this->form_validation->set_rules('password', 'Password', 'required|trim|matches[user.password]', [
                        'matches' => 'Wrong password!',
                    ]);

                    if ($this->form_validation->run() == false) {

                        $this->load->view('templates/auth-header', $data);
                        $this->load->view('auth/login');
                        $this->load->view('templates/auth-footer');
                    }
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                This email is not activated!
                </div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Email is not registered!
                </div>');
            redirect('auth');
        }
    }


    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'is_unique' => 'This email has already registered!'
        ]);
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', [
            'matches' => 'Password dont match!',
            'min_length' => 'Password too short!'
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'User Registration';
            $this->load->view('templates/auth-header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth-footer');
        } else {

            $upload_image = $_FILES['image']['name'];
            if ($upload_image) {
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size']      = '5000';
                $config['upload_path']  = './assets/img/profile/';

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('image')) {
                    $new_image = $this->upload->data('file_name');
                    $email = $this->input->post('email');

                    $data = [
                        'name' => htmlspecialchars($this->input->post('name', true)),
                        'email' => htmlspecialchars($email),
                        'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                        'image' => $new_image,
                        'role_id' => 2,
                        'is_active' => 0,
                        'date_created' => time()

                    ];

                    $token = base64_encode(random_bytes(32));
                    $user_token = [
                        'email' => $email,
                        'token' => $token,
                        'date_created' => time()
                    ];


                    $this->db->insert('user', $data);
                    $this->db->insert('user_token', $user_token);

                    $this->_sendEmail($token, 'verify');

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    Congratulation! your account has been created. Please activated your account
                    </div>');
                    redirect('auth');
                } else {
                    echo $this->upload->display_errors();
                }
            }
        }
    }


    private function _sendEmail($token, $type)
    {
        $config = array();
        $config['useragent']           = "CodeIgniter";
        $config['mailpath']            = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
        $config['protocol']            = "smtp";
        $config['smtp_host']           = "ssl://smtp.googlemail.com";
        $config['smtp_port']           = 465;
        $config['smtp_user'] = 'alfintomy08@gmail.com';
        $config['smtp_pass']  = 'tbjdlpdnblwztjua';
        $config['mailtype'] = 'html';
        $config['charset']  = 'utf-8';
        $config['newline']  = "\r\n";
        $config['wordwrap'] = TRUE;

        $this->load->library('email');

        $this->email->initialize($config);


        $this->email->from('alfintomy08@gmail.com', 'Alfin Tommy Nahuway');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Account Verification');
            $this->email->message('Click this link to verify your account : <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '"> Activate </a>');
        } else if ($type == 'forgot') {
            $this->email->subject('Reset Password');
            $this->email->message('Click this link to reset password your account : <a href="' . base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '"> Reset Password </a>');
        }


        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }



    public function verify()
    {
        $email =  $this->input->get('email');
        $token = $this->input->get('token');
        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                if (time() - $user['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');

                    $this->db->delete('user_token', ['email' => $email]);

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">' . $email . ' has been activated! Please login</div>');
                    redirect('auth');
                } else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! Token expired. </div>');
                    redirect('auth');
                }
            } else {

                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! Wrong token. </div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! Wrong email. </div>');
            redirect('auth');
        }
    }

    public function forgotPassword()
    {
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth-header', $data);
            $this->load->view('auth/forgot-password');
            $this->load->view('templates/auth-footer');
        } else {
            $email = $this->input->post('email');
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

            if ($user) {
                $token = base64_encode(random_bytes(32));
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time()
                ];

                $this->db->insert('user_token', $user_token);
                $this->_sendEmail($token, 'forgot');

                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Please check your email to reset password!</div>');
                redirect('auth/forgotpassword');
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Forgot password failed! Email not registered and activated!. </div>');
                redirect('auth/forgotpassword');
            }
        }
    }

    public function resetPassword()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                $this->session->set_userdata('reset_email', $email);
                $this->changePassword();
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Forgot password failed! Wrong token.</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Forgot password failed! Wrong email.</div>');
            redirect('auth');
        }
    }

    public function changePassword()
    {
        $this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[3]|matches[password2]', [
            'matches' => 'Password dont match!',
            'min_length' => 'Password too short!'
        ]);
        $this->form_validation->set_rules('password2', 'Repeat Password', 'trim|required|min_length[3]|matches[password1]', [
            'matches' => 'Password dont match!',
            'min_length' => 'Password too short!'
        ]);

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Change Password';
            $this->load->view('templates/auth-header', $data);
            $this->load->view('auth/change-password');
            $this->load->view('templates/auth-footer');
        } else {
            if (!$this->session->userdata('reset_email')) {
                redirect('auth');
            }
            $email = $this->session->userdata('reset_email');
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');
            $this->db->delete('user_token', ['email' => $email]);

            $this->session->unset_userdata('reset_email');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password has been changed! Please login.</div>');
            redirect('auth');
        }
    }


    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');

        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            You have been logged out!
            </div>');
        redirect('auth');
    }
}
