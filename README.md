#Postmark API Wrapper for CodeIgniter

A library for CodeIgniter 2.0+ which extends the Core CI_Email class.

##Installation

1. Copy config/postmark.php to your application/config/ folder
2. Copy libraries/Postmark.php to your application/libraries/ folder

##Using the Library

###Configuration

There is only one setting you need to update in the config file (application/config/postmark.php) and that is your Postmark API key. You can find your API key from the Server Details -> Credentials page in your Postmark Account (http://postmarkapp.com)

	$config['postmark_api_key'] = "YOUR_API_KEY_HERE";

###Loading the Library

In order to use the library, you will need to load it along with the Core CI_Email library (because we extend it).

	$this->load->library('email');
	$this->load->library('postmark');

OR

	$this->load->library(array('email', 'postmark'));

Just make sure to load the Core CI_Email (email) class first.

###Sending an Email

The great thing about extending the Core CI_Email class is the ability to not have to change the way you use the class! The only difference is that you will be using $this->postmark->send() instead of $this->email->send().

##Contact

If you'd like to request an update, report bugs or contact me for any other reason, email me at [jrtashjian@gmail.com](jrtashjian@gmail.com)
