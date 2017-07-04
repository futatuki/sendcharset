<?php

/**
 * Select sending charset coding system.
 */
class sendcharset extends rcube_plugin
{
  public $task = 'mail|settings';
  private $rc;

  function init()
  {
    $this->rc = rcmail::get_instance();
    $this->load_config();

    if ($this->rc->task == 'settings') {
      $this->add_hook('preferences_list', array($this, 'show_option'));
      $this->add_hook('preferences_save', array($this, 'save'));
    }
    else if ($this->rc->action == 'compose') {
      /* ($this->rc->task == 'mail') */
      $this->add_hook('template_object_composebody', array($this, 'append'));
    }
    else if ($this->rc->action == 'send') {
      $this->add_hook('message_ready', array($this, 'tweak_encoding'));
    }
  }

  /**
   * Show an option in compose preference.
   */
  function show_option($attrib)
  {
    if ($attrib['section'] != 'compose') {
      return $attrib;
    }
    $dont_override = $this->rc->config->get('dont_override', array());
    $this->add_texts('localization/', true);

    if (!in_array('sendcharset', $dont_override)) {
      $field_id = 'rcmfd_sendcharset';
      $selected = $this->get_charset();
      $input = $this->rc->output->charset_selector(array('name'=>'_sendcharset',
						       'id'=>$field_id,
						       'selected'=>$selected));
      $attrib['blocks']['main']['options']['sendcharset'] =
	array( 'title'=> html::label($field_id, $this->gettext('sendcharset')),
	       'content'=>$input);
    }
    if (!in_array('use_base64', $dont_override)) {
      // add checkbox for use_base64
      $field_id = 'rcmfd_use_base64';
      $checkbox = new html_checkbox(array('name' => '_use_base64', 'id' => $field_id, 'value' => 1));
      $attrib['blocks']['advanced']['options']['use_base64'] = array(
		'title'   => html::label($field_id, $this->gettext('usebase64')),
		'content' => $checkbox->show(intval($this->rc->config->get('use_base64', false)))
      );
    }
    return $attrib;
  }

  /**
   * Save preference option "sendcharset".
   */
  function save($attrib) {
    if ($attrib['section'] != 'compose') {
      return $attrib;
    }
    $dont_override = $this->rc->config->get('dont_override', array());
    if (!in_array('sendcharset', $dont_override)) {
      if (isset($_POST['_sendcharset'])) {
	$attrib['prefs']['sendcharset'] =
	  rcube_utils::get_input_value('_sendcharset', RCUBE_INPUT_POST);
      }
    }
    if (!in_array('use_base64', $dont_override)) {
      $attrib['prefs']['use_base64'] = (bool) rcube_utils::get_input_value('_use_base64', rcube_utils::INPUT_POST);
    }
    return $attrib;
  }

  /**
   * Add hidden input element to end of composebody
   */
  function append($attrib)
  {
    $input = new html_inputfield(array('type'=>'hidden', 'name'=>'_charset'));
    $attrib['content'] .= "\n".$input->show($this->get_charset());
    return $attrib;
  }

  /**
   * hack the encodings of the message before sending it
   */
  function tweak_encoding($params)
  {
    $MAIL_MIME = $params['message'];
    /* this is abuse ... */
    $message_charset = $MAIL_MIME->getParam('html_charset');
    if (preg_match('/iso-2022/i', $message_charset)) {
      $MAIL_MIME->setParam('head_encoding', 'base64');
      $MAIL_MIME->setParam('text_encoding', '7bit');
      if (preg_match('/format=flowed/', $MAIL_MIME->getParam('text_charset'))){
	$MAIL_MIME->setParam('text_charset', $message_charset . ";\r\n format=flowed");
      }
      else {
	$MAIL_MIME->setParam('text_charset', $message_charset);
      }
    }
    else if (   ! preg_match('/iso-8859/i', $message_charset)
            and $this->rc->config->get('use_base64', false)) {
      if ($MAIL_MIME->getParam('html_encoding') == 'quoted-printable') {
        $MAIL_MIME->setParam('html_encoding', 'base64');
      }
      if ($MAIL_MIME->getParam('head_encoding') == 'quoted-printable') {
        $MAIL_MIME->setParam('head_encoding', 'base64');
      }
      if ($MAIL_MIME->getParam('text_encoding') == 'quoted-printable') {
	$MAIL_MIME->setParam('text_encoding', 'base64');
      }
    }
    return $params;
  }

  /**
   * Get preference option "sendcharset".
   * If it is not set, return GUI coding system.
   */
  private function get_charset() {
    global $OUTPUT;

    return $this->rc->config->get('sendcharset', $OUTPUT->get_charset());
  }
}
?>
