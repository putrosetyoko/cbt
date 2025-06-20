<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public $data = [];

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library(['ion_auth', 'form_validation', 'session']);
		$this->load->helper(['url', 'language']);
		// Baris ini sekarang seharusnya bekerja dengan benar
		if (isset($this->form_validation)) { // Tambahkan pengecekan untuk lebih aman (opsional)
			$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));
	}
		$this->lang->load('auth');
	}

	public function output_json($data)
	{
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

	// ========================================================================
    // FUNGSI BARU UNTUK MEMBUAT ADMIN DARURAT
    // ========================================================================
    // public function buat_admin_darurat($secret_key = "")
    // {
    //     // Ganti 'KUNCI_RAHASIA_ANDA' dengan string yang sangat sulit ditebak
    //     // Ini untuk sedikit pengamanan agar tidak sembarang orang bisa mengakses URL ini
    //     $expected_secret_key = "admin"; 

    //     if ($secret_key !== $expected_secret_key) {
    //         echo "Akses ditolak. Kunci rahasia tidak valid.";
    //         return;
    //     }

    //     $username = 'admin'; // Atau username yang Anda inginkan
    //     $password = 'admin'; // Ganti dengan password yang aman
    //     $email    = 'admin@gmail.com'; // Ganti dengan email yang valid
        
    //     $additional_data = [
    //         'first_name' => 'Super',
    //         'last_name'  => 'Admin',
    //     ];
    //     $group_id = [1]; // ID grup untuk 'admin' (biasanya 1 di Ion Auth)

    //     // Cek apakah username atau email sudah ada
    //     if ($this->ion_auth->username_check($username)) {
    //         echo "Gagal! Username '{$username}' sudah ada.";
    //         return;
    //     }
    //     if ($this->ion_auth->email_check($email)) {
    //         echo "Gagal! Email '{$email}' sudah ada.";
    //         return;
    //     }

    //     // Buat user baru
    //     $user_id = $this->ion_auth->register($username, $password, $email, $additional_data, $group_id);

    //     if ($user_id) {
    //         echo "Berhasil! Akun admin baru telah dibuat.<br>";
    //         echo "Username: " . $username . "<br>";
    //         echo "Password: " . $password . "<br>";
    //         echo "Email: " . $email . "<br>";
    //         echo "Silakan login dan SEGERA HAPUS atau amankan fusngsi ini ('buat_admin_darurat') dari controller Auth.php!";
    //     } else {
    //         echo "Gagal membuat akun admin baru. Error: " . $this->ion_auth->errors();
    //     }
    // }
	

		public function index()
    {
        if ($this->ion_auth->logged_in()) { 
            $current_ion_user = $this->ion_auth->user()->row(); // Objek user dari Ion Auth
            $user_id_ion_auth = $current_ion_user->id; // ID dari tabel 'users' Ion Auth

            $guru_data_from_db = null;

            // Coba dapatkan data guru berdasarkan NIP (jika username adalah NIP)
            if (!empty($current_ion_user->username)) {
                $guru_data_from_db = $this->db->get_where('guru', ['nip' => $current_ion_user->username])->row();
            }

            // Jika tidak ketemu, coba berdasarkan email (pastikan email unik di tabel guru jika metode ini dipakai)
            if (!$guru_data_from_db && !empty($current_ion_user->email)) {
                $guru_data_from_db = $this->db->get_where('guru', ['email' => $current_ion_user->email])->row();
            }

            // Alternatif lain: jika tabel 'guru' memiliki kolom 'user_id' yang merujuk ke 'users.id' Ion Auth
            // if (!$guru_data_from_db) {
            //     $guru_data_from_db = $this->db->get_where('guru', ['user_id' => $user_id_ion_auth])->row();
            // }


            if ($guru_data_from_db && isset($guru_data_from_db->id_guru)) { // Pastikan id_guru ada
                $session_data_for_guru = [
                    // 'guru_id' ini haruslah ID yang sama dengan yang tersimpan di tb_soal.guru_id
                    'guru_id'   => $guru_data_from_db->id_guru, // Pastikan 'id_guru' adalah nama kolom Primary Key di tabel 'guru' Anda
                    'nama_guru' => $guru_data_from_db->nama_guru,
                    // Anda bisa menambahkan data lain seperti NIP, email guru jika perlu
                ];
                $this->session->set_userdata($session_data_for_guru);
                log_message('debug', 'Auth: Guru session data set for guru_id: ' . $guru_data_from_db->id_guru);
            } else {
                log_message('debug', 'Auth: Guru data not found in guru table for user: ' . $current_ion_user->username . ' or email: ' . $current_ion_user->email);
            }
            redirect('dashboard'); // Redirect setelah semua proses selesai
        }

        // Kode untuk menampilkan form login jika belum login
        $this->data['identity'] = [
            'name' => 'identity',
            'id' => 'identity',
            'type' => 'text',
            'placeholder' => 'Email', // Sesuaikan placeholder
            'autofocus' => 'autofocus',
            'class' => 'form-control',
            'autocomplete' => 'off',
            'value' => $this->form_validation->set_value('identity'), // Untuk re-populate jika validasi gagal
        ];
        $this->data['password'] = [
            'name' => 'password',
            'id' => 'password',
            'type' => 'password',
            'placeholder' => 'Password',
            'class' => 'form-control',
        ];
        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

        $this->load->view('_templates/auth/_header.php'); // Asumsi path template header
        $this->load->view('auth/login', $this->data);    // Asumsi path view login
        $this->load->view('_templates/auth/_footer.php'); // Asumsi path template footer
    }

	public function cek_login()
	{
		$this->form_validation->set_rules('identity', str_replace(':', '', $this->lang->line('login_identity_label')), 'required|trim');
		$this->form_validation->set_rules('password', str_replace(':', '', $this->lang->line('login_password_label')), 'required|trim');

		if ($this->form_validation->run() === TRUE)	{
			$remember = (bool)$this->input->post('remember');
			if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember)){
				$this->cek_akses();
			}else {
				$data = [
					'status' => false,
					'failed' => 'Incorrect Login',
				];
				$this->output_json($data);
			}
		}else{
			$invalid = [
				'identity' => form_error('identity'),
				'password' => form_error('password')
			];
			$data = [
				'status' 	=> false,
				'invalid' 	=> $invalid
			];
			$this->output_json($data);
		}
	}

	public function cek_akses()
	{
		if (!$this->ion_auth->logged_in()){
			$status = false; // jika false, berarti login gagal
			$url = 'auth'; // url untuk redirect
		}else{
			$status = true; // jika true maka login berhasil
			$url = 'dashboard';
		}

		$data = [
			'status' => $status,
			'url'	 => $url
		];
		$this->output_json($data);
	}

	public function logout()
	{
		$this->ion_auth->logout();
		redirect('login','refresh');
	}

	/**
	 * Forgot password
	 */
	public function forgot_password()
	{
		$this->data['title'] = $this->lang->line('forgot_password_heading');
		
		// setting validation rules by checking whether identity is username or email
		if ($this->config->item('identity', 'ion_auth') != 'email')
		{
			$this->form_validation->set_rules('identity', $this->lang->line('forgot_password_identity_label'), 'required');
		}
		else
		{
			$this->form_validation->set_rules('identity', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
		}


		if ($this->form_validation->run() === FALSE)
		{
			$this->data['type'] = $this->config->item('identity', 'ion_auth');
			// setup the input
			$this->data['identity'] = [
				'name' 	=> 'identity',
				'id'	=> 'identity',
				'class'	=> 'form-control',
				'autocomplete'	=> 'off',
				'autofocus'	=> 'autofocus'
			];

			if ($this->config->item('identity', 'ion_auth') != 'email')
			{
				$this->data['identity_label'] = $this->lang->line('forgot_password_identity_label');
			}
			else
			{
				$this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
			}

			// set any errors and display the form
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			$this->load->view('_templates/auth/_header', $this->data);
			$this->load->view('auth/forgot_password');
			$this->load->view('_templates/auth/_footer');
		}
		else
		{
			$identity_column = $this->config->item('identity', 'ion_auth');
			$identity = $this->ion_auth->where($identity_column, $this->input->post('identity'))->users()->row();

			if (empty($identity))
			{

				if ($this->config->item('identity', 'ion_auth') != 'email')
				{
					$this->ion_auth->set_error('forgot_password_identity_not_found');
				}
				else
				{
					$this->ion_auth->set_error('forgot_password_email_not_found');
				}

				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect("auth/forgot_password", 'refresh');
			}

			// run the forgotten password method to email an activation code to the user
			$forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});

			if ($forgotten)
			{
				// if there were no errors
				$this->session->set_flashdata('success', $this->ion_auth->messages());
				redirect("auth/forgot_password", 'refresh'); //we should display a confirmation page here instead of the login page
			}
			else
			{
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect("auth/forgot_password", 'refresh');
			}
		}
	}

	/**
	 * Reset password - final step for forgotten password
	 *
	 * @param string|null $code The reset code
	 */
	public function reset_password($code = NULL)
	{
		if (!$code)
		{
			show_404();
		}

		$this->data['title'] = $this->lang->line('reset_password_heading');
		
		$user = $this->ion_auth->forgotten_password_check($code);

		if ($user)
		{
			// if the code is valid then display the password reset form

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
			$this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

			if ($this->form_validation->run() === FALSE)
			{
				// display the form

				// set the flash data error message if there is one
				$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

				$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
				$this->data['new_password'] = [
					'name' => 'new',
					'id' => 'new',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['new_password_confirm'] = [
					'name' => 'new_confirm',
					'id' => 'new_confirm',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['user_id'] = [
					'name' => 'user_id',
					'id' => 'user_id',
					'type' => 'hidden',
					'value' => $user->id,
				];
				$this->data['csrf'] = $this->_get_csrf_nonce();
				$this->data['code'] = $code;

				// render
				$this->load->view('_templates/auth/_header');
				$this->load->view('auth/reset_password', $this->data);
				$this->load->view('_templates/auth/_footer');
			}
			else
			{
				$identity = $user->{$this->config->item('identity', 'ion_auth')};

				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id'))
				{

					// something fishy might be up
					$this->ion_auth->clear_forgotten_password_code($identity);

					show_error($this->lang->line('error_csrf'));

				}
				else
				{
					// finally change the password
					$change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

					if ($change)
					{
						// if the password was successfully changed
						$this->session->set_flashdata('message', $this->ion_auth->messages());
						redirect("auth/login", 'refresh');
					}
					else
					{
						$this->session->set_flashdata('message', $this->ion_auth->errors());
						redirect('auth/reset_password/' . $code, 'refresh');
					}
				}
			}
		}
		else
		{
			// if the code is invalid then send them back to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("auth/forgot_password", 'refresh');
		}
	}

	/**
	 * Activate the user
	 *
	 * @param int         $id   The user ID
	 * @param string|bool $code The activation code
	 */
	public function activate($id, $code = FALSE)
	{
		$activation = FALSE;

		if ($code !== FALSE)
		{
			$activation = $this->ion_auth->activate($id, $code);
		}
		else if ($this->ion_auth->is_admin())
		{
			$activation = $this->ion_auth->activate($id);
		}

		if ($activation)
		{
			// redirect them to the auth page
			$this->session->set_flashdata('message', $this->ion_auth->messages());
			redirect("auth", 'refresh');
		}
		else
		{
			// redirect them to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("auth/forgot_password", 'refresh');
		}
	}

	/**
	 * @return array A CSRF key-value pair
	 */
	public function _get_csrf_nonce()
	{
		$this->load->helper('string');
		$key = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return [$key => $value];
	}

	/**
	 * @return bool Whether the posted CSRF token matches
	 */
	public function _valid_csrf_nonce(){
		$csrfkey = $this->input->post($this->session->flashdata('csrfkey'));
		if ($csrfkey && $csrfkey === $this->session->flashdata('csrfvalue'))
		{
			return TRUE;
		}
			return FALSE;
	}

	public function _render_page($view, $data = NULL, $returnhtml = FALSE)//I think this makes more sense
	{

		$viewdata = (empty($data)) ? $this->data : $data;

		$view_html = $this->load->view($view, $viewdata, $returnhtml);

		// This will return html on 3rd argument being true
		if ($returnhtml)
		{
			return $view_html;
		}
	}

}
