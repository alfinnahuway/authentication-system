<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{
    public function index()
    {

        if (!$this->session->userdata('email')) {
            redirect('auth');
        }
        $email = $this->session->userdata('email');
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        $data = [
            'title' => 'Landing Page',
            'user' => $user
        ];
        $this->load->view('templates/landing-header', $data);
        $this->load->view('templates/landing-sidebar', $data);
        $this->load->view('templates/landing-topbar', $data);
        $this->load->view('landing-page/index');
        $this->load->view('templates/landing-footer');
    }
}
