<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Contact Plugin
 *
 * Build and send contact forms
 *
 * @package		PyroCMS
 * @author		PyroCMS Dev Team
 * @copyright	Copyright (c) 2008 - 2010, PyroCMS
 *
 */
class Plugin_Contact extends Plugin
{
	// Fields must match this certain criteria
	private $rules = array(
		array(
			'field'	=> 'contact_name',
			'label'	=> 'lang:contact_name_label',
			'rules'	=> 'required|trim|max_length[80]'
		),
		array(
			'field'	=> 'contact_email',
			'label'	=> 'lang:contact_email_label',
			'rules'	=> 'required|trim|valid_email|max_length[80]'
		),
		array(
			'field'	=> 'company_name',
			'label'	=> 'lang:contact_company_name_label',
			'rules'	=> 'trim|max_length[80]'
		),
		array(
			'field'	=> 'subject',
			'label'	=> 'lang:contact_subject_label',
			'rules'	=> 'required|trim'
		),
		array(
			'field'	=> 'message',
			'label'	=> 'lang:contact_message_label',
			'rules'	=> 'required'
		)
	);

	public function __construct()
	{
		$this->lang->load('contact');
		
		$this->default_subjects = array(
			'support'   => lang('subject_support'),
			'sales'     => lang('subject_sales'),
			'payments'  => lang('subject_payments'),
			'business'  => lang('subject_business'),
			'feedback'  => lang('subject_feedback'),
			'other'     => lang('subject_other')
		);
	}
	
	/**
	 * Form
	 *
	 * Insert a form template
	 *
	 * Usage:
	 *
	 * {pyro:contact:form subjects=""}
	 *
	 * @param	array
	 * @return	array
	 */
	function form()
	{
		$this->load->library('form_validation');
		$this->load->helper('form');

		// Set the message subject		
		if ($this->attribute('subjects') && $subjects = explode('|', $this->attribute('subjects')))
		{
			$subjects = array_combine($subjects, $subjects);
		}

		else
		{
			$subjects = $this->default_subjects;
		}

		$this->form_validation->set_rules($this->rules);

		// If the user has provided valid information
		if ($this->form_validation->run())
		{
			// The try to send the email
			if ($this->_send_email())
			{
				$message = $this->attribute('confirmation', lang('contact_sent_text'));

				// Store this session to limit useage
				$this->session->set_flashdata('success', $message);
			}

			else
			{
				$message = $this->attribute('error', lang('contact_error_message'));

				$this->session->set_flashdata('error', $message);
			}

			redirect(current_url());
		}

		// Set the values for the form inputs
		foreach ($this->rules as $rule)
		{
			$form_values->{$rule['field']} = set_value($rule['field']);
		}

		return $this->module_view('contact', 'form', array('subjects' => $subjects, 'form_values' => $form_values), TRUE);
	}


	function _send_email($subjects = array())
	{
		$this->load->library('email');
		$this->load->library('user_agent');

		// If "other subject" exists then use it, if not then use the selected subject
		$subject = ($this->input->post('other_subject')) ? $this->input->post('other_subject') : $this->default_subjects[$this->input->post('subject')];
		
		// Loop through cleaning data and inputting to $data
		$data = $this->input->post();
		
		// Add in some extra details
		$data['subject']		= 	$subject;
		$data['sender_agent']	=	$this->agent->browser().' '.$this->agent->version();
		$data['sender_ip']		=	$this->input->ip_address();
		$data['sender_os']		=	$this->agent->platform();
		$data['slug'] 			= 	'contact';
		$data['email'] 			= 	$data['contact_email'];
		$data['name']			= 	$data['contact_name'];

		// If the email has sent with no known erros, show the message
		return (Events::trigger('email', $data) !== FALSE);
	}
}

/* End of file plugin.php */