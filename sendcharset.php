<?php

/**
 * Select sending charset coding system.
 */
class sendcharset extends rcube_plugin
{

  function init()
  {
    $this->load_config();
    $this->add_hook('preferences_list', array($this, 'show_option'));
    $this->add_hook('preferences_save', array($this, 'save'));
    $this->add_hook('template_object_composebody', array($this, 'append'));
    $this->add_hook('message_ready', array($this, 'tweak_encoding'));
  }

  /**
   * Show an option in compose preference.
   */
  function show_option($attrib)
  {
    $rcmail = rcmail::get_instance();

    if ($attrib['section'] == 'compose') {
      $field_id = 'rcmfd_sendcharset';
      $selected = $this->get_charset();
      $input = $rcmail->output->charset_selector(array('name'=>'_sendcharset',
						       'id'=>$field_id,
						       'selected'=>$selected));
      $attrib['blocks']['main']['options']['sendcharset'] =
	array( 'title'=>html::label($field_id, $this->gettext(rcube_label('charset'))),
	       'content'=>$input);

      // add checkbox for use_base64
      $field_id = 'rcmfd_use_base64';
      $checkbox = new html_checkbox(array('name' => '_use_base64', 'id' => $field_id, 'value' => 1));
      $attrib['blocks']['main']['options']['use_base64'] = array(
		'title'   => $this->gettext('usebase64'),
		'content' => $checkbox->show(intval($rcmail->config->get('use_base64', false)))
      );
    }
    return $attrib;
  }

  /**
   * Save preference option "sendcharset".
   */
  function save($attrib) {
    if ($attrib['section'] == 'compose') {
      if (isset($_POST['_sendcharset'])) {
	$attrib['prefs']['sendcharset'] =
	  get_input_value('_sendcharset', RCUBE_INPUT_POST);
      }
      $attrib['prefs']['use_base64'] = rcube_utils::get_input_value('_use_base64', rcube_utils::INPUT_POST) ? true : false;
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
    $rcmail = rcmail::get_instance();
    $config = $rcmail->config->all();
    $MAIL_MIME = $params['message'];
    /* this is abuse ... */
    $message_charset = $MAIL_MIME->getParam('html_charset');
    $txt_headers = $MAIL_MIME->txtHeaders();
    if (isset($config['use_base64']) and $config['use_base64']) {
      $MAIL_MIME->setParam('html_encoding', 'base64');
      $MAIL_MIME->setParam('head_encoding', 'base64');
      if ($MAIL_MIME->getParam('text_encoding') == 'quoted-printable') {
	$MAIL_MIME->setParam('text_encoding', 'base64');
      }
    }
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
    return $params;
  }

  /**
   * Get preference option "sendcharset".
   * If it is not set, return GUI coding system.
   */
  private function get_charset() {
    global $OUTPUT;
    $rcmail = rcmail::get_instance();
    $config = $rcmail->config->all();

    return isset($config['sendcharset']) ?
      $config['sendcharset'] : $OUTPUT->get_charset();
  }
}
