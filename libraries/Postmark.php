<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Postmark API Wrapper Library
 *
 * Extends the CodeIgniter Email library to use Postmarkapp.com's API
 *
 * #version		1.0
 * @author		JR Tashjian (jrtashjian@gmail.com)
 * @link		http://github.com/jrtashjian/postmark-codeigniter
 */
class Postmark extends CI_Email {

	private $api_key;
	private $api_url = 'http://api.postmarkapp.com/email';

	// using https?
	private $use_ssl = FALSE;

	private $tag;
	private $attachments = array();

	public function __construct()
	{
		parent::__construct();

		// get the CodeIgniter Object
		$CI =& get_instance();

		// load config
		$CI->load->config('postmark');

		// load helpers
		$CI->load->helper('file');

		$this->api_key = $CI->config->item('postmark_api_key');
	}

	/**
	 * Enables use of SSL (https)
	 *
	 * @param	boolean		on or off (TRUE or FALSE)
	 * @return	Postmark
	 */
	public function set_ssl( $ssl=FALSE )
	{
		$this->api_url = str_replace('http', 'https', $this->api_url);
		$this->use_ssl = $ssl;

		return $this;
	}

	/**
	 * Overrides the CI_Email subject function because it seems to set the
	 * subject in the _headers property array instead of the _subject property.
	 *
	 * @param	string		the email subject
	 * @return	Postmark
	 */
	public function subject( $subject )
	{
		parent::subject($subject);
		$this->_subject = $subject;

		return $this;
	}

	/**
	 * Sets the tag used to identify the email in Postmark.
	 *
	 * @param	string		tag name
	 * @return	Postmark
	 */
	public function tag( $tag )
	{
		$this->tag = $tag;

		return $this;
	}

	/**
	 * Prepares the email data for submission to the Postmark API.
	 *
	 * @return	boolean
	 */
	private function prepare_data()
	{
		$data = array(
			'Subject' => $this->_subject,
			'From' => $this->_headers['From'],
			'To' => $this->_recipients,
		);

		if( !empty($this->_headers['Cc']) )
		{
			$data['Cc'] = $this->_headers['Cc'];
		}

		if( !empty($this->_headers['Bcc']) )
		{
			$data['Bcc'] = $this->_headers['Bcc'];
		}

		if( !empty($this->_headers['Reply-To']) )
		{
			$data['ReplyTo'] = $this->_headers['Reply-To'];
		}

		if( $this->mailtype != 'html' )
		{
			$data['TextBody'] = ($this->wordwrap === TRUE) ? $this->word_wrap($this->_body) : $this->_body;
		}
		else
		{
			$data['HtmlBody'] = $this->_body;
			$data['TextBody'] = ($this->wordwrap === TRUE) ? $this->word_wrap($this->alt_message) : $this->alt_message;
		}

		if( !empty($this->tag) )
		{
			$data['Tag'] = $this->tag;
		}

		if( !empty($this->_attach_name) )
		{
			$total_size = 0;

			foreach( $this->_attach_name as $key => $value )
			{
				// throw error if file cannot be found
				if( !file_exists($value) )
				{
					show_error('Postmark Library: Could not attach file `'.$value.'`. File could not be found.');
				}

				if( !$this->valid_attachment($value) )
				{
					show_error('Postmark Library: Could not attach file `'.$value.'`. File type not allowed.');
				}

				$file_info = get_file_info($value);
				$total_size += $file_info['size'];
			}

			// if sum of attachment sizes are greater than 10MB - fail.
			if( $total_size > 10485760 )
			{
				show_error('Postmark Library: Could not send email. Maximum attachment size reached.');
			}
			else
			{
				foreach( $this->_attach_name as $key => $value )
				{
					$file_data = read_file($value);

					$data['Attachments'][] = array(
						'Name' => basename($value),
						'Content' => chunk_split(base64_encode($file_data)),
						'ContentType' => get_mime_by_extension($value)
					);
				}
			}
		}

		return $data;
	}

	/**
	 * Makes the request to the Postmark API.
	 *
	 * @return	boolean
	 */
	public function send()
	{
		$data = $this->prepare_data();

		$headers = array(
			'Accept: application/json',
			'Content-Type: applications/json',
			'X-Postmark-Server-Token: ' . $this->api_key
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if( $this->use_ssl )
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$return = json_decode(curl_exec($ch));
		$curl_error = curl_error($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if( $http_code != 200 )
		{
			show_error('Postmark API: '.$return->Message, $return->ErrorCode);
		}

		return TRUE;
	}

	/**
	 * Validates an attachment based on what is allowed in the Postmark API.
	 *
	 * @param	string		the filepath
	 * @return	boolean
	 */
	private function valid_attachment( $file )
	{
		$valid_file_types = array(
			'gif', 'jpg', 'jpeg', 'png', 'swf', 'flv', 'avi', 'mpg', 'mp3', 'wav', 'rm', 'mov', 'psd', 'ai', 'tif', 'tiff',
			'txt', 'rtf', 'htm', 'html', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'ps', 'eps',
			'log', 'csv', 'ics', 'xml',
		);

		$file_info = pathinfo($file);

		if( !in_array($file_info['extension'], $valid_file_types) )
		{
			return FALSE;
		}

		return TRUE;
	}
}

/* End of file Postmark.php */
/* Location: ./application/libraries/Postmark.php */
