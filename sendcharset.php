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
    $dont_override = $this->rc->config->get('dont_override', array());

    if ($this->rc->task == 'settings') {
      $this->add_hook('preferences_list', array($this, 'show_option'));
      $this->add_hook('preferences_save', array($this, 'save'));
    }
    else if ($this->rc->action == 'compose') {
      /* ($this->rc->task == 'mail') */
      if (   ! $this->rc->config->get('use_sendcharset_selector', False)
          or in_array('sendcharset', $dont_override)) {
        $this->add_hook('template_object_composebody', array($this, 'append'));
      }
      else {
        if ($this->rc->config->get('skin') == 'larry') {
          $this->add_hook('template_container', array($this, 'add_selector'));
        }
        else if ($this->rc->config->get('skin') == 'classic') {
          $this->add_hook('render_page',
                  array($this, 'insert_selector_classic'));
        }
        else {
          $this->add_hook('template_object_composebody',
                  array($this, 'append'));
        }
      }
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
   * Add charset selector in composeoptions template container
   * (currently, 'larry' skin only)
   */
  function add_selector($attrib)
  {
    if ($attrib['name'] != 'composeoptions') {
        return $attrib;
    }
    $this->add_texts('localization/', true);

    $larry_template =
"        <span class=\"composeoption\">
                <label>%s %s</label>
        </span>
";
    $label    =  html::label($field_id, $this->gettext('sendcharset'));
    $field_id = 'rcmcomposecharset';
    $selected = $this->get_charset();
    $input = $this->rc->output->charset_selector(array(
        'name'     => '_charset',
        'id'       => $field_id,
        'selected' => $selected
    ));
    $attrib['content'] .= sprintf($larry_template, $label, $input);
    return $attrib;
  }

  /**
   * insert charset selector classic 'compose' page
   */
  function insert_selector_classic($attrib)
  {
    if ($attrib['template'] != 'compose') {
      return $attrib;
    }
    $this->add_texts('localization/', true);

    $classic_template = '${1}
        <tr>
        <td><label for="rcmcomposecharset">%s:</label></td>
        <td>%s</td>
    </tr>${2}';

    $label    =  $this->gettext('sendcharset');
    $field_id = 'rcmcomposecharset';
    $selected = $this->get_charset();
    $input = $this->rc->output->charset_selector(array(
        'name'     => '_charset',
        'id'       => $field_id,
        'selected' => $selected
    ));
    $attrib['content'] = preg_replace(
        '((<div\s+id\s*=\s*"composeoptionsmenu"[\s\S]+\S)(\s*</table>\s*</div>))Umi',
        sprintf($classic_template, $label, $input), $attrib['content']);
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
