<?php

/**
 * Language Selector on Logon Page
 *
 * Switch language from Logon Page and adjust user language
 *  if language selector was touched
 *  else don't update users's language preference !
 *
 *
 * @version 1.3 - 14.11.2010
 * @author Roland Liebl
 * @website http://myroundcube.googlecode.com
 * @licence GNU GPL
 */

/**
 *
 * Usage: http://mail4us.net/myroundcube/
 *
 **/

class lang_sel extends rcube_plugin
{

  function init(){

    $this->_load_config('lang_sel');
    $this->_load_config('settings');
    $this->add_hook('template_object_loginform', array($this, 'show_selector'));
    $this->add_hook('startup', array($this, 'switch_lang_before_login'));
    $this->add_hook('login_after', array($this, 'switch_lang_after_login'));

  }

  function _load_config($plugin)  {
    $rcmail = rcmail::get_instance();
    $plugins = array_flip($rcmail->config->get('plugins'));
    if(isset($plugins[$plugin])){
      $config = "plugins/" . $plugin . "/config/config.inc.php";
      if(file_exists($config))
        include $config;
      else if(file_exists($config . ".dist"))
        include $config . ".dist";
      if(is_array($rcmail_config)){
        $rcmail->config->merge($rcmail_config);
      }
    }
  }

  function show_selector($p){

    $rcmail = rcmail::get_instance();
    $limit_langs = array_flip((array)$rcmail->config->get('limit_languages', array()));
    $avail_langs = $rcmail->list_languages();
    if(count($limit_langs) > 0){
      foreach($limit_langs as $key => $val)
        $limit_langs[$key] = $avail_langs[$key];
      $langs = $limit_langs;
    }
    else
      $langs = $avail_langs;

    $selector = '<select onchange="document.location.href=\'./?_action=plugin.lang_sel&_lang_sel=\' + this.value" name="_lang_sel">' . "\n";
    asort($langs);
    foreach($langs as $key => $val){
      if($_SESSION['language'] == $key)
        $selected = " selected=\"selected\"";
      else
        $selected = "";
      $selector .= '<option value="' . $key . "\"$selected>" . $val . "</option>\n";
    }
    $selector .= "</select>\n";

    $content = $p['content'];
    
    $content = str_replace(
      "</tbody>",
      '<tr><td class="title"><label for="rcmlangsel">' . $rcmail->gettext('language') . '</label></td>' . "\n" .
      '<td class="input">' . $selector . '</td></tr>' .
      "</tbody>\n",
      $content
    );
    if(rcube_utils::get_input_value('_lang_sel', RCUBE_INPUT_POST))
      $prior_lang = rcube_utils::get_input_value('_lang_sel', RCUBE_INPUT_POST);
    else
      $prior_lang = $_SESSION['language'];

    $p['content'] = $content;

    return $p;

  }

  function switch_lang_before_login($args){
    if(rcube_utils::get_input_value('_lang_sel', RCUBE_INPUT_GET) && count($_POST) == 0){
      if(rcube_utils::get_input_value('_action', RCUBE_INPUT_GET) == "plugin.lang_sel"){
        $rcmail = rcmail::get_instance();
        if(isset($_SESSION['username'])){
          $rcmail->output->redirect(array());
        }
        else{
          $lang = rcube_utils::get_input_value('_lang_sel', RCUBE_INPUT_GET);
          $add = $this->add_plugin_localization($lang);
          $rcmail->load_language($lang,$add);
          $rcmail->output->send("login");        }
      }
    }
    else{
      if(rcube_utils::get_input_value('_user', RCUBE_INPUT_POST) && rcube_utils::get_input_value('_pass', RCUBE_INPUT_POST)){
        $args['action'] = 'login';
      }
    }
    return $args;
  }

  function switch_lang_after_login($args){
    $lang = rcube_utils::get_input_value('_lang_sel', RCUBE_INPUT_POST);
    if(empty($lang))
      return $args;
    if(strtolower($lang) != strtolower($_SESSION['language'])){
      $rcmail = rcmail::get_instance();
      $a_prefs = $rcmail->user->get_prefs();
      $a_prefs['language'] = $lang;
      $a_prefs['auth_time'] = $_SESSION['auth_time'];
      $rcmail->load_language($lang);
      $rcmail->user->save_prefs($a_prefs);
      $_SESSION['language'] = $lang;
    }

    return $args;

  }

  function add_plugin_localization($lang){
    $rcmail = rcmail::get_instance();
    $langs = $rcmail->config->get('lang_sel_localizations');
    $add = array();
    if(is_array($langs)){
      foreach($langs as $plugin => $folder){
        if(class_exists($plugin,false)){
          if(! @include "./plugins/" . $plugin ."/" . $folder . "/" . $lang . ".inc")
            @include "./plugins/" . $plugin ."/" . $folder . "/en_US.inc";
          $texts = (array)$labels + (array)$messages + (array)$texts;
          foreach ($texts as $key => $value)
            $add[$plugin.'.'.$key] = $value;
        }
      }
    }

    return $add;

  }

}

?>
