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
    else { /* if ($this->rc->task == 'mail') */
      $this->add_hook('template_object_composebody', array($this, 'append'));
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

    if (!in_array('sendcharset', $dont_override)) {
      $this->add_texts('localization/', true);
      $field_id = 'rcmfd_sendcharset';
      $selected = $this->get_charset();
      $input = $this->rc->output->charset_selector(array('name'=>'_sendcharset',
						       'id'=>$field_id,
						       'selected'=>$selected));
      $attrib['blocks']['main']['options']['sendcharset'] =
	array( 'title'=>html::label($field_id, $this->gettext('sendcharset')),
	       'content'=>$input);
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
	  get_input_value('_sendcharset', RCUBE_INPUT_POST);
      }
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
   * Get preference option "sendcharset".
   * If it is not set, return GUI coding system.
   */
  private function get_charset() {
    global $OUTPUT;
    $config = $this->rc->config->all();

    return isset($config['sendcharset']) ?
      $config['sendcharset'] : $OUTPUT->get_charset();
  }
}
